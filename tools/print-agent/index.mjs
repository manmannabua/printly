#!/usr/bin/env node
// Printly reference print agent.
//
// A minimal local service that polls the Printly cloud for print jobs, downloads
// each file, "prints" it, and reports status back — the same contract a real
// in-store agent implements (planning §5.3). Node 18+ (global fetch), no deps.
//
// Config via env (or a .env file next to this script):
//   PRINTLY_API_URL      base URL of the API      (default http://localhost:8000)
//   PRINTLY_AGENT_TOKEN  the agent's bearer token (required)
//   POLL_INTERVAL_MS     poll cadence             (default 3000)
//   PRINT_MODE           virtual | lp             (default virtual)
//   SPOOL_DIR            where files are saved     (default ./spool)

import { spawn } from 'node:child_process'
import { mkdirSync, writeFileSync, readFileSync, existsSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const here = dirname(fileURLToPath(import.meta.url))

// Tiny .env loader (KEY=VALUE lines) so you don't have to export vars.
const envPath = join(here, '.env')
if (existsSync(envPath)) {
  for (const line of readFileSync(envPath, 'utf8').split('\n')) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/)
    if (m && !process.env[m[1]]) process.env[m[1]] = m[2].replace(/^["']|["']$/g, '')
  }
}

const API = (process.env.PRINTLY_API_URL || 'http://localhost:8000').replace(/\/$/, '')
const TOKEN = process.env.PRINTLY_AGENT_TOKEN
const INTERVAL = Number(process.env.POLL_INTERVAL_MS || 3000)
const MODE = process.env.PRINT_MODE || 'virtual'
const SPOOL = process.env.SPOOL_DIR || join(here, 'spool')

if (!TOKEN) {
  console.error('✗ PRINTLY_AGENT_TOKEN is required (create an agent in the app to get one).')
  process.exit(1)
}
mkdirSync(SPOOL, { recursive: true })

const auth = { Authorization: `Bearer ${TOKEN}`, Accept: 'application/json' }

async function api(path, init = {}) {
  const res = await fetch(`${API}${path}`, { ...init, headers: { ...auth, ...(init.headers || {}) } })
  if (!res.ok) throw new Error(`${init.method || 'GET'} ${path} → ${res.status}`)
  return res
}

/** "Print" a spooled file. virtual = log only; lp = pipe to the CUPS lp command. */
function printFile(path, copies) {
  if (MODE === 'lp') {
    return new Promise((resolve, reject) => {
      const p = spawn('lp', ['-n', String(copies), path], { stdio: 'inherit' })
      p.on('error', reject)
      p.on('exit', (code) => (code === 0 ? resolve() : reject(new Error(`lp exited ${code}`))))
    })
  }
  console.log(`   🖨  [virtual] printed ${copies}× ${path}`)
  return Promise.resolve()
}

async function handleJob(job) {
  console.log(`→ job ${job.id} (order ${job.order_code}): ${job.file?.name} ×${job.copies}`)
  try {
    // Download the file.
    const res = await api(`/api/agent/jobs/${job.id}/file`)
    const buf = Buffer.from(await res.arrayBuffer())
    const dest = join(SPOOL, `${job.id}-${job.file?.name || 'document'}`)
    writeFileSync(dest, buf)

    // Report printing, do the print, report done.
    await api(`/api/agent/jobs/${job.id}`, {
      method: 'PATCH', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'printing' }),
    })
    await printFile(dest, job.copies || 1)
    await api(`/api/agent/jobs/${job.id}`, {
      method: 'PATCH', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'done' }),
    })
    console.log(`   ✓ done`)
  } catch (err) {
    console.error(`   ✗ ${err.message}`)
    try {
      await api(`/api/agent/jobs/${job.id}`, {
        method: 'PATCH', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: 'error', error: String(err.message).slice(0, 500) }),
      })
    } catch { /* the next poll will surface connectivity issues */ }
  }
}

async function tick() {
  try {
    const { data } = await (await api('/api/agent/jobs')).json()
    for (const job of data) await handleJob(job)
  } catch (err) {
    console.error(`poll failed: ${err.message}`)
  }
}

async function main() {
  try {
    const { data } = await (await api('/api/agent/me')).json()
    console.log(`✓ connected as "${data.agent.name}" — ${data.printers.length} printer(s), mode=${MODE}`)
  } catch (err) {
    console.error(`✗ could not authenticate: ${err.message}`)
    process.exit(1)
  }
  console.log(`Polling ${API}/api/agent/jobs every ${INTERVAL}ms …`)
  // eslint-disable-next-line no-constant-condition
  while (true) {
    await tick()
    await new Promise((r) => setTimeout(r, INTERVAL))
  }
}

main()
