<!-- eslint-disable camelcase -->
<script setup>
import { useCourierStore } from '@/stores/courier'
import { useOrderStore } from '@/stores/order'
import { onMounted, reactive, ref } from 'vue'

const orderStore = useOrderStore()
const courierStore = useCourierStore()

// UI States
const activeTab = ref('all')
const isDetailDialogVisible = ref(false)
const isSubmitLoading = ref(false)

// Selected Data
const selectedOrder = ref(null)
const selectedCourierId = ref(null)

// --- 1. Notification System (Snackbar) ---
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

// --- 2. Custom Confirmation Dialog System ---
const confirmDialog = reactive({
  show: false,
  title: '',
  message: '',
  color: 'primary',
  icon: 'tabler-help',
  onConfirm: null,
})

const openConfirm = (title, message, color, icon, callback) => {
  confirmDialog.title = title
  confirmDialog.message = message
  confirmDialog.color = color
  confirmDialog.icon = icon
  confirmDialog.onConfirm = callback
  confirmDialog.show = true
}

const handleConfirmAction = async () => {
  if (confirmDialog.onConfirm) {
    isSubmitLoading.value = true
    await confirmDialog.onConfirm()
    isSubmitLoading.value = false
    confirmDialog.show = false
  }
}

// --- Configuration & Helpers ---
onMounted(() => {
  orderStore.fetchOrders()
})

const statusConfig = {
  pending: { color: 'warning', label: 'Menunggu Konfirmasi', icon: 'tabler-clock', bg: 'bg-warning' },
  processing: { color: 'info', label: 'Sedang Dibuat', icon: 'tabler-chef-hat', bg: 'bg-info' },
  on_delivery: { color: 'primary', label: 'Sedang Diantar', icon: 'tabler-truck-delivery', bg: 'bg-primary' },
  completed: { color: 'success', label: 'Selesai', icon: 'tabler-circle-check', bg: 'bg-success' },
  cancelled: { color: 'error', label: 'Dibatalkan', icon: 'tabler-x', bg: 'bg-error' },
}

const timelineSteps = [
  { status: 'pending', label: 'Masuk' },
  { status: 'processing', label: 'Dibuat' },
  { status: 'on_delivery', label: 'Diantar' },
  { status: 'completed', label: 'Selesai' },
]

const getCurrentStepIndex = status => {
  if (status === 'cancelled') return -1
  
  return timelineSteps.findIndex(s => s.status === status)
}

const formatRupiah = val => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)

const formatDate = dateString => {
  if (!dateString) return '-'
  
  return new Date(dateString).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const handleFilter = status => {
  activeTab.value = status
  orderStore.fetchOrders({ status: status === 'all' ? null : status })
}

const openDetail = order => {
  selectedOrder.value = order
  selectedCourierId.value = null
  if (order.status === 'processing') {
    courierStore.fetchCouriers()
  }
  isDetailDialogVisible.value = true
}

// --- Actions ---

// Process Order (Pending -> Processing)
const triggerProcessOrder = () => {
  openConfirm(
    'Mulai Produksi?',
    'Status pesanan akan berubah menjadi "Sedang Dibuat". Pastikan bahan tersedia.',
    'info',
    'tabler-chef-hat',
    async () => {
      const result = await orderStore.updateStatus(selectedOrder.value.id, 'processing')
      if (result.success) {
        showSnackbar('Pesanan diproses ke dapur.')
        isDetailDialogVisible.value = false
      } else {
        showSnackbar(result.message, 'error')
      }
    },
  )
}

// Assign Courier (Processing -> On Delivery)
const triggerAssignCourier = () => {
  if (!selectedCourierId.value) {
    showSnackbar('Harap pilih kurir terlebih dahulu.', 'error')
    
    return
  }

  const courierName = courierStore.couriers.find(c => c.id === selectedCourierId.value)?.name || 'Kurir'

  // FIX: Removed markdown bolding (**) to satisfy ESLint vue/no-v-html rule
  openConfirm(
    'Kirim Pesanan?',
    `Pesanan akan diserahkan kepada ${courierName}. Status berubah menjadi "Sedang Diantar".`,
    'primary',
    'tabler-truck-delivery',
    async () => {
      const result = await orderStore.updateStatus(selectedOrder.value.id, 'on_delivery', selectedCourierId.value)
      if (result.success) {
        showSnackbar('Pesanan berhasil diserahkan ke kurir.')
        isDetailDialogVisible.value = false
      } else {
        showSnackbar(result.message, 'error')
      }
    },
  )
}
</script>

<template>
  <div>
    <h2 class="text-h4 font-weight-bold mb-4">
      Daftar Pesanan
    </h2>

    <div class="d-flex gap-2 mb-4 overflow-x-auto">
      <VBtn 
        v-for="status in ['all', 'pending', 'processing', 'on_delivery', 'completed']"
        :key="status"
        :variant="activeTab === status ? 'flat' : 'outlined'"
        :color="activeTab === status ? 'primary' : 'secondary'"
        size="small"
        class="text-capitalize"
        @click="handleFilter(status)"
      >
        {{ status === 'all' ? 'Semua' : statusConfig[status]?.label }}
      </VBtn>
    </div>

    <VCard>
      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th>ID</th>
            <th>Waktu</th>
            <th>Pelanggan</th>
            <th>Total</th>
            <th>Status</th>
            <th class="text-center">
              Detail
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in orderStore.orders"
            :key="order.id"
          >
            <td class="text-disabled">
              #{{ order.id }}
            </td>
            <td class="text-caption">
              {{ formatDate(order.created_at) }}
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ order.customer_name }}</span>
              </div>
            </td>
            <td class="font-weight-bold">
              {{ formatRupiah(order.total_price) }}
            </td>
            <td>
              <VChip
                :color="statusConfig[order.status].color"
                size="small"
                variant="tonal"
              >
                {{ statusConfig[order.status].label }}
              </VChip>
            </td>
            <td class="text-center">
              <VBtn
                icon
                variant="text"
                color="primary"
                @click="openDetail(order)"
              >
                <VIcon icon="tabler-eye" />
              </VBtn>
            </td>
          </tr>
          <tr v-if="orderStore.orders.length === 0">
            <td
              colspan="6"
              class="text-center pa-4 text-disabled"
            >
              Tidak ada data pesanan.
            </td>
          </tr>
        </tbody>
      </VTable>

      <VCardText class="d-flex justify-end pt-2">
        <VPagination
          v-model="orderStore.pagination.current_page"
          :length="orderStore.pagination.last_page"
          :total-visible="5"
          size="small"
          @update:model-value="(page) => orderStore.fetchOrders({ page, status: activeTab === 'all' ? null : activeTab })"
        />
      </VCardText>
    </VCard>

    <VDialog
      v-model="isDetailDialogVisible"
      max-width="900"
      scrollable
    >
      <VCard
        v-if="selectedOrder"
        class="overflow-hidden"
      >
        <div :class="`pa-6 ${statusConfig[selectedOrder.status].bg} text-white d-flex justify-space-between align-start`">
          <div>
            <div class="text-overline mb-1 opacity-80">
              Order #{{ selectedOrder.id }}
            </div>
            <h3 class="text-h4 font-weight-bold d-flex align-center gap-2">
              <VIcon :icon="statusConfig[selectedOrder.status].icon" />
              {{ statusConfig[selectedOrder.status].label }}
            </h3>
            <div class="mt-2 opacity-90">
              <VIcon
                icon="tabler-calendar"
                size="16"
                class="me-1"
              /> 
              {{ formatDate(selectedOrder.created_at) }}
            </div>
          </div>
          <VBtn
            icon
            variant="text"
            color="white"
            @click="isDetailDialogVisible = false"
          >
            <VIcon
              icon="tabler-x"
              size="24"
            />
          </VBtn>
        </div>

        <VCardText class="pa-0">
          <div
            v-if="selectedOrder.status !== 'cancelled'"
            class="bg-surface pa-4 border-b"
          >
            <VTimeline
              direction="horizontal"
              line-inset="12"
              density="compact"
              align="start"
              truncate-line="start"
            >
              <VTimelineItem 
                v-for="(step, index) in timelineSteps" 
                :key="step.status"
                :dot-color="getCurrentStepIndex(selectedOrder.status) >= index ? 'primary' : 'grey-lighten-2'"
                :icon="getCurrentStepIndex(selectedOrder.status) >= index ? 'tabler-check' : ''"
                size="small"
              >
                <div class="text-caption font-weight-bold">
                  {{ step.label }}
                </div>
              </VTimelineItem>
            </VTimeline>
          </div>

          <VRow no-gutters>
            <VCol
              cols="12"
              md="8"
              class="pa-6 border-e"
            >
              <h4 class="text-h6 font-weight-bold mb-4">
                Rincian Menu
              </h4>
              <VTable
                density="compact"
                class="text-no-wrap mb-4 border rounded"
              >
                <thead class="bg-grey-lighten-4">
                  <tr>
                    <th>Menu</th>
                    <th class="text-center">
                      Qty
                    </th>
                    <th class="text-end">
                      Harga
                    </th>
                    <th class="text-end">
                      Total
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in selectedOrder.items"
                    :key="item.id"
                  >
                    <td class="py-2">
                      <div class="font-weight-medium">
                        {{ item.product?.name }}
                      </div>
                    </td>
                    <td class="text-center font-weight-bold">
                      x{{ item.quantity }}
                    </td>
                    <td class="text-end text-disabled">
                      {{ formatRupiah(item.price) }}
                    </td>
                    <td class="text-end font-weight-bold">
                      {{ formatRupiah(item.price * item.quantity) }}
                    </td>
                  </tr>
                </tbody>
              </VTable>
              <div class="d-flex justify-end">
                <div style="min-inline-size: 250px;">
                  <div class="d-flex justify-space-between mb-2">
                    <span class="text-disabled">Subtotal</span>
                    <span class="font-weight-medium">{{ formatRupiah(selectedOrder.total_price - selectedOrder.shipping_cost) }}</span>
                  </div>
                  <div class="d-flex justify-space-between mb-2">
                    <span class="text-disabled">Ongkos Kirim</span>
                    <span class="font-weight-medium">{{ formatRupiah(selectedOrder.shipping_cost) }}</span>
                  </div>
                  <VDivider class="my-2" />
                  <div class="d-flex justify-space-between align-center">
                    <span class="text-h6 font-weight-bold">Total</span>
                    <span class="text-h5 font-weight-bold text-primary">{{ formatRupiah(selectedOrder.total_price) }}</span>
                  </div>
                </div>
              </div>
            </VCol>

            <VCol
              cols="12"
              md="4"
              class="pa-6 bg-grey-lighten-5"
            >
              <VCard
                flat
                border
                class="mb-4"
              >
                <VCardItem>
                  <template #prepend>
                    <VAvatar
                      color="primary"
                      variant="tonal"
                      rounded
                    >
                      <VIcon icon="tabler-user" />
                    </VAvatar>
                  </template>
                  <VCardTitle class="text-subtitle-1">
                    {{ selectedOrder.customer_name }}
                  </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText class="py-3">
                  <div class="d-flex gap-2">
                    <VIcon
                      icon="tabler-map-pin"
                      size="18"
                      class="text-primary mt-1"
                    />
                    <span class="text-body-2">{{ selectedOrder.shipping_address }}</span>
                  </div>
                </VCardText>
              </VCard>

              <div class="mt-6">
                <h5 class="text-subtitle-2 text-disabled mb-3 text-uppercase">
                  Tindakan
                </h5>

                <div v-if="selectedOrder.status === 'pending'">
                  <p class="text-caption mb-3">
                    Pesanan baru. Cek pembayaran dan stok.
                  </p>
                  <VBtn
                    block
                    color="info"
                    size="large"
                    :loading="isSubmitLoading"
                    @click="triggerProcessOrder"
                  >
                    <VIcon
                      start
                      icon="tabler-chef-hat"
                    /> Proses Pesanan
                  </VBtn>
                </div>

                <div v-else-if="selectedOrder.status === 'processing'">
                  <p class="text-caption mb-2">
                    Pilih kurir untuk mengantar pesanan ini.
                  </p>
                  <VSelect
                    v-model="selectedCourierId"
                    :items="courierStore.couriers.filter(c => c.courier_status === 'available')"
                    item-title="name"
                    item-value="id"
                    label="Pilih Kurir"
                    variant="outlined"
                    bg-color="white"
                    class="mb-3"
                    density="compact"
                  >
                    <template #item="{ props, item }">
                      <VListItem
                        v-bind="props"
                        :subtitle="item.raw.email"
                      />
                    </template>
                  </VSelect>
                  <VBtn
                    block
                    color="primary"
                    size="large"
                    :disabled="!selectedCourierId"
                    :loading="isSubmitLoading"
                    @click="triggerAssignCourier"
                  >
                    <VIcon
                      start
                      icon="tabler-truck-delivery"
                    /> Kirim Pesanan
                  </VBtn>
                </div>

                <div v-else-if="selectedOrder.status === 'on_delivery'">
                  <VCard
                    border
                    flat
                    class="bg-blue-grey-lighten-5 text-center py-4"
                  >
                    <VAvatar
                      color="primary"
                      class="mb-2"
                    >
                      <VIcon icon="tabler-truck" />
                    </VAvatar>
                    <div class="text-caption">
                      Kurir Pengantar
                    </div>
                    <div class="font-weight-bold">
                      {{ selectedOrder.courier?.name }}
                    </div>
                  </VCard>
                </div>

                <div v-else-if="selectedOrder.status === 'completed'">
                  <VAlert
                    type="success"
                    variant="tonal"
                    border="start"
                    density="compact"
                  >
                    Pesanan Selesai.
                  </VAlert>
                </div>
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="confirmDialog.show"
      max-width="400"
      persistent
    >
      <VCard class="text-center pa-4">
        <VCardText class="d-flex flex-column align-center gap-2">
          <VAvatar
            :color="confirmDialog.color"
            variant="tonal"
            size="64"
            class="mb-2"
          >
            <VIcon
              :icon="confirmDialog.icon"
              size="32"
            />
          </VAvatar>
          <h3 class="text-h5 font-weight-bold">
            {{ confirmDialog.title }}
          </h3>
          <p class="text-body-2 text-medium-emphasis mb-4">
            {{ confirmDialog.message }}
          </p>
        </VCardText>
        <VCardActions class="justify-center gap-2">
          <VBtn
            variant="outlined"
            color="secondary"
            class="px-6"
            @click="confirmDialog.show = false"
          >
            Batal
          </VBtn>
          <VBtn 
            :color="confirmDialog.color" 
            variant="flat" 
            :loading="isSubmitLoading" 
            class="px-6"
            @click="handleConfirmAction"
          >
            Ya, Lanjutkan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar
      v-model="snackbar.show"
      location="top end"
      :color="snackbar.color"
      variant="flat"
      transition="slide-x-reverse-transition"
    >
      <div class="d-flex align-center gap-2">
        <VIcon :icon="snackbar.icon" />
        <span class="font-weight-medium">{{ snackbar.text }}</span>
      </div>
    </VSnackbar>
  </div>
</template>
