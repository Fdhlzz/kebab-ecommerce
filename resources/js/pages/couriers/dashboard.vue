<script setup>
import { useCourierDeliveryStore } from '@/stores/courierDelivery'
import { onMounted, reactive, ref } from 'vue'

const store = useCourierDeliveryStore()
const activeTab = ref('active') 

const isSubmitLoading = ref(false)

// --- 1. Notification State (Snackbar) ---
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
  icon: 'tabler-circle-check',
})

const showSnackbar = (text, type = 'success') => {
  snackbar.text = text
  snackbar.color = type === 'success' ? 'success' : 'error'
  snackbar.icon = type === 'success' ? 'tabler-circle-check' : 'tabler-alert-circle'
  snackbar.show = true
}

const confirmDialog = reactive({ 
  show: false, 
  orderId: null, 
})

onMounted(() => {
  store.fetchOrders('active')
  store.fetchOrders('history')
})

const formatRupiah = val => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)

const openGoogleMaps = rawAddress => {
  if (!rawAddress) return

  // 1. Split by " (" to remove label (e.g. "(Rumah)")
  let cleanAddress = rawAddress.split(' (')[0]

  // 2. Safety check: If no label exists, try splitting by " - " to remove phone
  if (cleanAddress === rawAddress && rawAddress.includes(' - ')) {
    cleanAddress = rawAddress.split(' - ')[0]
  }

  // 3. Encode for URL (converts spaces to %20, etc.)
  const encodedAddress = encodeURIComponent(cleanAddress)

  // 4. Open Google Maps
  window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank')
}

const initiateCompletion = orderId => {
  confirmDialog.orderId = orderId
  confirmDialog.show = true
}

const handleComplete = async () => {
  if (!confirmDialog.orderId) return

  isSubmitLoading.value = true

  const result = await store.completeOrder(confirmDialog.orderId)

  isSubmitLoading.value = false
  confirmDialog.show = false

  if (result.success) {
    showSnackbar('Pengantaran Selesai! Kerja bagus.')
  } else {
    showSnackbar(result.message, 'error')
  }
}

const refresh = () => {
  store.fetchOrders(activeTab.value)
}
</script>

<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4">
      <div>
        <h2 class="text-h5 font-weight-bold">
          Halo, Kurir!
        </h2>
        <p class="text-body-2 text-disabled">
          Siap mengantar kebahagiaan?
        </p>
      </div>
      <VBtn
        icon
        variant="text"
        @click="refresh"
      >
        <VIcon icon="tabler-refresh" />
      </VBtn>
    </div>

    <VTabs
      v-model="activeTab"
      class="mb-4"
      grow
    >
      <VTab value="active">
        <VIcon
          start
          icon="tabler-truck-delivery"
        /> Tugas
      </VTab>
      <VTab value="history">
        <VIcon
          start
          icon="tabler-history"
        /> Riwayat
      </VTab>
    </VTabs>

    <VWindow v-model="activeTab">
      <VWindowItem value="active">
        <div
          v-if="store.loading"
          class="text-center py-4"
        >
          <VProgressCircular
            indeterminate
            color="primary"
          />
        </div>
        
        <div
          v-else-if="store.activeOrders.length === 0"
          class="text-center py-10"
        >
          <VAvatar
            color="secondary"
            variant="tonal"
            size="64"
            class="mb-3"
          >
            <VIcon
              icon="tabler-coffee"
              size="32"
            />
          </VAvatar>
          <h3 class="text-h6 font-weight-medium">
            Tidak ada tugas aktif
          </h3>
          <p class="text-body-2 text-disabled">
            Anda sedang santai (Available).
          </p>
        </div>

        <div
          v-else
          class="d-flex flex-column gap-4"
        >
          <VCard
            v-for="order in store.activeOrders"
            :key="order.id"
            elevation="2"
            border
            class="rounded-lg"
          >
            <div class="bg-primary text-white px-4 py-2 d-flex justify-space-between align-center">
              <span class="font-weight-bold">Order #{{ order.id }}</span>
              <span class="text-caption font-weight-bold text-uppercase">Sedang Diantar</span>
            </div>

            <VCardText class="pt-4">
              <div class="d-flex align-start gap-3 mb-3">
                <VAvatar
                  color="primary"
                  variant="tonal"
                  rounded
                >
                  <VIcon icon="tabler-user" />
                </VAvatar>
                <div>
                  <div class="font-weight-bold text-subtitle-1">
                    {{ order.customer_name }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">
                    Tagihan: <span class="text-primary font-weight-bold">{{ formatRupiah(Number(order.total_price) + Number(order.shipping_cost)) }}</span>
                  </div>
                </div>
              </div>

              <VDivider class="mb-3" />

              <div class="bg-grey-lighten-4 pa-3 rounded mb-4 d-flex gap-2 align-start">
                <VIcon
                  icon="tabler-map-pin"
                  color="error"
                  class="mt-1 flex-shrink-0"
                  size="18"
                />
                <span class="text-body-2 font-weight-medium">{{ order.shipping_address }}</span>
              </div>

              <VRow dense>
                <VCol cols="6">
                  <VBtn 
                    block 
                    variant="outlined" 
                    color="info" 
                    prepend-icon="tabler-map-2" 
                    @click="openGoogleMaps(order.shipping_address)"
                  >
                    Buka Map
                  </VBtn>
                </VCol>
                <VCol cols="6">
                  <VBtn 
                    block 
                    color="success" 
                    prepend-icon="tabler-check" 
                    @click="initiateCompletion(order.id)"
                  >
                    Selesai
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </div>
      </VWindowItem>

      <VWindowItem value="history">
        <div
          v-if="store.historyOrders.length === 0"
          class="text-center py-8 text-disabled"
        >
          Belum ada riwayat pengantaran.
        </div>
        <VCard
          v-for="order in store.historyOrders"
          :key="order.id"
          class="mb-3"
          variant="outlined"
        >
          <VCardItem>
            <template #prepend>
              <VIcon
                icon="tabler-package"
                class="text-disabled"
              />
            </template>
            <VCardTitle class="text-subtitle-2">
              Order #{{ order.id }}
            </VCardTitle>
            <VCardSubtitle class="text-caption">
              {{ order.customer_name }}
            </VCardSubtitle>
            <template #append>
              <div class="text-end">
                <VChip
                  color="success"
                  size="x-small"
                  class="mb-1"
                >
                  Selesai
                </VChip>
                <div class="text-caption font-weight-bold">
                  {{ formatRupiah(order.total_price) }}
                </div>
              </div>
            </template>
          </VCardItem>
        </VCard>
      </VWindowItem>
    </VWindow>

    <VDialog
      v-model="confirmDialog.show"
      max-width="340"
      persistent
    >
      <VCard class="text-center pa-4">
        <VCardText class="d-flex flex-column align-center">
          <VAvatar
            color="success"
            variant="tonal"
            size="64"
            class="mb-4"
          >
            <VIcon
              icon="tabler-circle-check"
              size="32"
            />
          </VAvatar>
          <h3 class="text-h6 font-weight-bold mb-2">
            Selesaikan Pengantaran?
          </h3>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Pastikan barang sudah diterima pelanggan.
          </p>
        </VCardText>
        <VCardActions class="justify-center gap-3 pt-2">
          <VBtn 
            variant="outlined" 
            color="secondary" 
            class="px-6"
            @click="confirmDialog.show = false"
          >
            Batal
          </VBtn>
          <VBtn 
            color="success" 
            variant="flat" 
            :loading="isSubmitLoading" 
            class="px-6"
            @click="handleComplete"
          >
            Ya, Selesai
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar
      v-model="snackbar.show"
      location="top"
      :color="snackbar.color"
      variant="flat"
      timeout="3000"
    >
      <div class="d-flex align-center gap-2">
        <VIcon
          :icon="snackbar.icon"
          color="white"
        />
        <span class="font-weight-medium text-white">{{ snackbar.text }}</span>
      </div>
    </VSnackbar>
  </div>
</template>
