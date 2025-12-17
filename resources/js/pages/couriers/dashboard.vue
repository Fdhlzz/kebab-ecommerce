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

// --- 2. Confirmation Dialog ---
const confirmDialog = reactive({ 
  show: false, 
  orderId: null,
  paymentMethod: '', // ✅ Added to track method
  totalAmount: 0,    // ✅ Added to show amount
})

onMounted(() => {
  store.fetchOrders('active')
  store.fetchOrders('history')
})

const formatRupiah = val => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)

const openGoogleMaps = rawAddress => {
  if (!rawAddress) return
  let cleanAddress = rawAddress.split(' (')[0]
  if (cleanAddress === rawAddress && rawAddress.includes(' - ')) {
    cleanAddress = rawAddress.split(' - ')[0]
  }
  const encodedAddress = encodeURIComponent(cleanAddress)

  window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank')
}

// ✅ Updated to capture order details for the dialog
const initiateCompletion = order => {
  confirmDialog.orderId = order.id
  confirmDialog.paymentMethod = order.payment_method
  confirmDialog.totalAmount = Number(order.total_price) + Number(order.shipping_cost)
  confirmDialog.show = true
}

const handleComplete = async () => {
  if (!confirmDialog.orderId) return

  isSubmitLoading.value = true

  // Ensure your store calls the update status endpoint with 'completed'
  const result = await store.completeOrder(confirmDialog.orderId)

  isSubmitLoading.value = false
  confirmDialog.show = false

  if (result.success) {
    showSnackbar('Pengantaran Selesai! Kerja bagus.')

    // Refresh lists
    store.fetchOrders('active')
    store.fetchOrders('history')
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
            <div
              class="px-4 py-2 d-flex justify-space-between align-center" 
              :class="order.payment_method === 'COD' ? 'bg-warning text-white' : 'bg-primary text-white'"
            >
              <span class="font-weight-bold">Order #{{ order.id }}</span>
              <span class="text-caption font-weight-bold text-uppercase">
                {{ order.payment_method === 'COD' ? 'TAGIH TUNAI (COD)' : 'SUDAH LUNAS (QRIS)' }}
              </span>
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
                  <div class="text-body-2 mt-1">
                    Tagihan: 
                    <span
                      class="font-weight-bold"
                      :class="order.payment_method === 'COD' ? 'text-warning' : 'text-success'"
                    >
                      {{ formatRupiah(Number(order.total_price) + Number(order.shipping_cost)) }}
                    </span>
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
                    @click="initiateCompletion(order)"
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
      max-width="360"
      persistent
    >
      <VCard class="text-center pa-4">
        <VCardText class="d-flex flex-column align-center">
          <VAvatar 
            :color="confirmDialog.paymentMethod === 'COD' ? 'warning' : 'success'" 
            variant="tonal"
            size="64"
            class="mb-4"
          >
            <VIcon
              :icon="confirmDialog.paymentMethod === 'COD' ? 'tabler-cash' : 'tabler-circle-check'"
              size="32"
            />
          </VAvatar>

          <h3 class="text-h6 font-weight-bold mb-2">
            {{ confirmDialog.paymentMethod === 'COD' ? 'Terima Uang Tunai?' : 'Selesaikan Pesanan?' }}
          </h3>

          <div v-if="confirmDialog.paymentMethod === 'COD'">
            <p class="text-body-2 mb-1">
              Pelanggan harus membayar:
            </p>
            <h2 class="text-h4 font-weight-bold text-warning mb-2">
              {{ formatRupiah(confirmDialog.totalAmount) }}
            </h2>
            <p class="text-caption text-disabled">
              Pastikan uang diterima sebelum klik Ya.
            </p>
          </div>

          <div v-else>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Pesanan ini sudah <b>LUNAS (QRIS)</b>.<br>Pastikan barang diterima pelanggan.
            </p>
          </div>
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
            {{ confirmDialog.paymentMethod === 'COD' ? 'Uang Diterima' : 'Selesai' }}
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
