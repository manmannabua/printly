import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { PrintAgent, Printer, PrinterCapabilities, PrintJob } from '@/types/printly'

const base = (storeId: string) => `/api/v1/stores/${storeId}`

// ── Print agents ─────────────────────────────────────────────────────────────
export type PrintAgentPayload = { name: string; is_active?: boolean }

export async function listAgents(storeId: string): Promise<PrintAgent[]> {
  const res = await api.get<ApiResponse<PrintAgent[]>>(`${base(storeId)}/print-agents`, { params: { all: true } })
  return res.data.data
}

export async function createAgent(storeId: string, payload: PrintAgentPayload): Promise<PrintAgent> {
  const res = await api.post<ApiResponse<PrintAgent>>(`${base(storeId)}/print-agents`, payload)
  return res.data.data
}

export async function updateAgent(storeId: string, id: string, payload: Partial<PrintAgentPayload>): Promise<PrintAgent> {
  const res = await api.put<ApiResponse<PrintAgent>>(`${base(storeId)}/print-agents/${id}`, payload)
  return res.data.data
}

export async function regenerateAgentToken(storeId: string, id: string): Promise<PrintAgent> {
  const res = await api.post<ApiResponse<PrintAgent>>(`${base(storeId)}/print-agents/${id}/regenerate-token`)
  return res.data.data
}

export async function deleteAgent(storeId: string, id: string): Promise<void> {
  await api.delete(`${base(storeId)}/print-agents/${id}`)
}

// ── Printers ─────────────────────────────────────────────────────────────────
export type PrinterPayload = {
  name: string
  print_agent_id?: string | null
  capabilities?: PrinterCapabilities
  is_active?: boolean
}

export async function listPrinters(storeId: string): Promise<Printer[]> {
  const res = await api.get<ApiResponse<Printer[]>>(`${base(storeId)}/printers`, { params: { all: true } })
  return res.data.data
}

export async function createPrinter(storeId: string, payload: PrinterPayload): Promise<Printer> {
  const res = await api.post<ApiResponse<Printer>>(`${base(storeId)}/printers`, payload)
  return res.data.data
}

export async function updatePrinter(storeId: string, id: string, payload: Partial<PrinterPayload>): Promise<Printer> {
  const res = await api.put<ApiResponse<Printer>>(`${base(storeId)}/printers/${id}`, payload)
  return res.data.data
}

export async function deletePrinter(storeId: string, id: string): Promise<void> {
  await api.delete(`${base(storeId)}/printers/${id}`)
}

// ── Print jobs ───────────────────────────────────────────────────────────────
export async function listJobs(storeId: string): Promise<PrintJob[]> {
  const res = await api.get<ApiResponse<PrintJob[]>>(`${base(storeId)}/print-jobs`, { params: { all: true } })
  return res.data.data
}

export async function retryJob(storeId: string, id: string): Promise<PrintJob> {
  const res = await api.post<ApiResponse<PrintJob>>(`${base(storeId)}/print-jobs/${id}/retry`)
  return res.data.data
}
