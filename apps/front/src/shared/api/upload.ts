/** openapi-fetch serializer for the multipart upload endpoints (single `file` field). */
export function fileBody(file: File) {
  return {
    body: {},
    bodySerializer: () => {
      const data = new FormData()
      data.append('file', file)
      return data
    },
  }
}
