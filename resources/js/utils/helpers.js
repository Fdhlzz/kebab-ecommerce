export const getImageUrl = path => {
  if (!path) return null
  
  if (path.startsWith('http')) return path

  const baseUrl = import.meta.env.VITE_STORAGE_URL || '/storage/'
  const cleanPath = path.startsWith('/') ? path.substring(1) : path
  
  return `${baseUrl}${cleanPath}`
}
