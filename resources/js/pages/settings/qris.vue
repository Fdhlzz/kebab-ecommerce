<script setup>
import { onMounted, ref } from 'vue'

const isLoading = ref(false)
const isUploading = ref(false)
const qrisUrl = ref(null)
const fileInput = ref(null)
const selectedFile = ref(null)

// Notification state
const snackbar = ref({ show: false, text: '', color: 'success' })

// Fetch current QRIS on load
const fetchQris = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('accessToken') // Adjust based on your auth logic

    const res = await fetch('/api/settings/qris', {
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })

    const data = await res.json()

    qrisUrl.value = data.url
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// Handle File Selection
const handleFileChange = e => {
  const file = e.target.files[0]
  if (file) {
    selectedFile.value = file

    // Create local preview immediately
    qrisUrl.value = URL.createObjectURL(file)
  }
}

// Upload Logic
const uploadQris = async () => {
  if (!selectedFile.value) return

  isUploading.value = true

  const formData = new FormData()

  formData.append('qris_image', selectedFile.value)

  try {
    const token = localStorage.getItem('accessToken')

    const res = await fetch('/api/settings/qris', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` }, // Do NOT set Content-Type for FormData
      body: formData,
    })

    if (res.ok) {
      const data = await res.json()

      qrisUrl.value = data.url
      snackbar.value = { show: true, text: 'QRIS Berhasil Diperbarui!', color: 'success' }
      selectedFile.value = null // Reset file input
    } else {
      throw new Error('Upload failed')
    }
  } catch (e) {
    snackbar.value = { show: true, text: 'Gagal mengupload gambar.', color: 'error' }
  } finally {
    isUploading.value = false
  }
}

onMounted(() => {
  fetchQris()
})
</script>

<template>
  <div>
    <VRow justify="center">
      <VCol
        cols="12"
        md="6"
      >
        <VCard title="Pengaturan QRIS">
          <VCardText>
            <div class="d-flex flex-column align-center gap-4 py-4">
              <div 
                class="qris-preview d-flex align-center justify-center border rounded bg-grey-lighten-4"
                style=" position: relative; overflow: hidden; block-size: 300px;inline-size: 300px;"
              >
                <VImg
                  v-if="qrisUrl"
                  :src="qrisUrl"
                  cover
                  class="w-100 h-100"
                />
                <div
                  v-else
                  class="text-center text-disabled"
                >
                  <VIcon
                    icon="tabler-qrcode"
                    size="64"
                    class="mb-2"
                  />
                  <p>Belum ada QRIS</p>
                </div>

                <div
                  v-if="isLoading || isUploading"
                  class="loading-overlay d-flex align-center justify-center"
                >
                  <VProgressCircular
                    indeterminate
                    color="primary"
                  />
                </div>
              </div>

              <p class="text-caption text-center text-medium-emphasis">
                Upload gambar QRIS Code toko Anda (Format: JPG/PNG, Max 2MB).
                <br>Gambar ini akan muncul di aplikasi pelanggan saat pembayaran.
              </p>

              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="d-none"
                @change="handleFileChange"
              >

              <div class="d-flex gap-3">
                <VBtn
                  variant="outlined"
                  color="secondary"
                  prepend-icon="tabler-photo"
                  @click="$refs.fileInput.click()"
                >
                  Pilih Gambar
                </VBtn>

                <VBtn
                  :disabled="!selectedFile"
                  :loading="isUploading"
                  color="primary"
                  prepend-icon="tabler-upload"
                  @click="uploadQris"
                >
                  Simpan QRIS
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VSnackbar 
      v-model="snackbar.show" 
      location="top end" 
      :color="snackbar.color"
    >
      {{ snackbar.text }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.qris-preview {
  border: 2px dashed #ddd !important;
  transition: all 0.3s ease;
}

.loading-overlay {
  position: absolute;
  z-index: 2;
  background: rgba(255, 255, 255, 80%);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}
</style>
