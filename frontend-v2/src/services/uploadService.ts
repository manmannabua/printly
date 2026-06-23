import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { Upload } from '@/types/printly'

export type UploadFolder = 'developers' | 'projects' | 'brokerages' | 'agents' | 'project-media' | 'unit-types'

/** Upload an image or document to a namespaced folder; returns its path + URL. */
export async function uploadFile(file: File, folder: UploadFolder): Promise<Upload> {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('folder', folder)

  const res = await api.post<ApiResponse<Upload>>('/api/v1/uploads', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return res.data.data
}

/** Remove a previously uploaded file by its stored path. */
export async function deleteFile(path: string): Promise<void> {
  await api.delete('/api/v1/uploads', { data: { path } })
}
