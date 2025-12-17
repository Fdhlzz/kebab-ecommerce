<script setup>
import { useCourierStore } from '@/stores/courier'
import { useOrderStore } from '@/stores/order'
import { onMounted, reactive, ref } from 'vue'

const orderStore = useOrderStore()
const courierStore = useCourierStore()

const STORAGE_URL = import.meta.env.VITE_STORAGE_URL

// UI States
const activeTab = ref('all')
const isDetailDialogVisible = ref(false)
const isSubmitLoading = ref(false)

// Selected Data
const selectedOrder = ref(null)
const selectedCourierId = ref(null)

// --- Notification & Confirm Dialog (Kept same logic, just refined UI) ---
const snackbar = reactive({ show: false, text: '', color: 'success', icon: 'tabler-circle-check' })

const showSnackbar = (text, type = 'success') => {
  snackbar.text = text
  snackbar.color = type === 'success' ? 'success' : 'error'
  snackbar.icon = type === 'success' ? 'tabler-circle-check' : 'tabler-alert-circle'
  snackbar.show = true
}

const confirmDialog = reactive({ show: false, title: '', message: '', color: 'primary', icon: 'tabler-help', onConfirm: null })

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

onMounted(() => { orderStore.fetchOrders() })

// --- Configuration ---
const statusConfig = {
  pending: { color: 'warning', label: 'Menunggu Konfirmasi', icon: 'tabler-clock', bg: 'bg-warning' },
  processing: { color: 'info', label: 'Sedang Dibuat', icon: 'tabler-chef-hat', bg: 'bg-info' },
  // eslint-disable-next-line camelcase
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

const getImageUrl = path => {
  if (!path) return null
  if (path.startsWith('http')) return path
  
  return `${STORAGE_URL}${path}`
}

const openImageInNewTab = path => {
  const url = getImageUrl(path)
  if (url) window.open(url, '_blank')
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
const triggerVerifyPayment = () => {
  openConfirm('Verifikasi Pembayaran?', 'Pastikan dana masuk. Status akan berubah menjadi "Sedang Dibuat".', 'success', 'tabler-cash', async () => {
    const result = await orderStore.updateStatus(selectedOrder.value.id, 'processing')
    if (result.success) { showSnackbar('Pembayaran diverifikasi.'); isDetailDialogVisible.value = false; orderStore.fetchOrders() }
    else { showSnackbar(result.message, 'error') }
  })
}

const triggerProcessOrder = () => {
  openConfirm('Mulai Produksi?', 'Status berubah menjadi "Sedang Dibuat".', 'info', 'tabler-chef-hat', async () => {
    const result = await orderStore.updateStatus(selectedOrder.value.id, 'processing')
    if (result.success) { showSnackbar('Pesanan diproses.'); isDetailDialogVisible.value = false }
    else { showSnackbar(result.message, 'error') }
  })
}

const triggerAssignCourier = () => {
  if (!selectedCourierId.value) { showSnackbar('Pilih kurir dulu.', 'error') 

    return }
  const courierName = courierStore.couriers.find(c => c.id === selectedCourierId.value)?.name || 'Kurir'

  openConfirm('Kirim Pesanan?', `Serahkan ke ${courierName}.`, 'primary', 'tabler-truck-delivery', async () => {
    const result = await orderStore.updateStatus(selectedOrder.value.id, 'on_delivery', selectedCourierId.value)
    if (result.success) { showSnackbar('Berhasil diserahkan.'); isDetailDialogVisible.value = false }
    else { showSnackbar(result.message, 'error') }
  })
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
            <th>Pembayaran</th>
            <th>Total</th>
            <th>Status</th>
            <th class="text-center">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in orderStore.orders"
            :key="order.id"
          >
            <td class="text-disabled font-weight-medium">
              #{{ order.id }}
            </td>
            <td class="text-caption text-medium-emphasis">
              {{ formatDate(order.created_at) }}
            </td>
            <td><span class="font-weight-medium">{{ order.customer_name }}</span></td>
            
            <td>
              <div class="d-flex align-center gap-2">
                <VChip
                  size="small"
                  :color="order.payment_method === 'QRIS' || 'COD' ? 'purple' : 'grey'"
                  variant="flat"
                  class="font-weight-bold"
                >
                  {{ order.payment_method }}
                </VChip>
                <VChip
                  size="small"
                  :color="order.payment_status === 'paid' ? 'success' : 'error'"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  {{ order.payment_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
                </VChip>
              </div>
            </td>

            <td class="font-weight-bold text-high-emphasis">
              {{ formatRupiah(Number(order.total_price) + Number(order.shipping_cost)) }}
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
              colspan="7"
              class="text-center pa-8 text-disabled"
            >
              <VIcon
                icon="tabler-box-off"
                size="40"
                class="mb-2"
              />
              <div>Tidak ada data pesanan</div>
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
            <div class="mt-2 opacity-90 d-flex align-center">
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
              <h4 class="text-h6 font-weight-bold mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-receipt"
                  size="20"
                /> Rincian Menu
              </h4>
              <VTable
                density="compact"
                class="text-no-wrap mb-4 border rounded"
              >
                <thead class="bg-grey-lighten-4">
                  <tr>
                    <th>Menu</th><th class="text-center">
                      Qty
                    </th><th class="text-end">
                      Harga
                    </th><th class="text-end">
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
                    <span class="text-disabled">Subtotal</span><span class="font-weight-medium">{{ formatRupiah(selectedOrder.total_price) }}</span>
                  </div>
                  <div class="d-flex justify-space-between mb-2">
                    <span class="text-disabled">Ongkos Kirim</span><span class="font-weight-medium">{{ formatRupiah(selectedOrder.shipping_cost) }}</span>
                  </div>
                  <VDivider class="my-2" />
                  <div class="d-flex justify-space-between align-center">
                    <span class="text-h6 font-weight-bold">Total Bayar</span>
                    <span class="text-h5 font-weight-bold text-primary">{{ formatRupiah(Number(selectedOrder.total_price) + Number(selectedOrder.shipping_cost)) }}</span>
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

              <VCard
                v-if="selectedOrder.payment_method === 'QRIS'"
                flat
                border
                class="mb-4"
              >
                <VCardItem class="bg-purple-lighten-5">
                  <template #prepend>
                    <VIcon
                      icon="tabler-qrcode"
                      color="purple"
                    />
                  </template>
                  <VCardTitle class="text-subtitle-2 text-purple font-weight-bold">
                    Pembayaran QRIS
                  </VCardTitle>
                  <template #append>
                    <VChip
                      size="x-small"
                      :color="selectedOrder.payment_status === 'paid' ? 'success' : 'error'"
                    >
                      {{ selectedOrder.payment_status === 'paid' ? 'LUNAS' : 'UNPAID' }}
                    </VChip>
                  </template>
                </VCardItem>
                <VDivider />
                <VCardText class="text-center py-4">
                  <div v-if="selectedOrder.payment_proof">
                    <VImg
                      :src="getImageUrl(selectedOrder.payment_proof)"
                      max-height="200"
                      class="rounded border cursor-pointer mb-2 elevation-2"
                      cover
                      @click="openImageInNewTab(selectedOrder.payment_proof)"
                    />
                    <div
                      class="text-caption text-primary cursor-pointer d-flex align-center justify-center gap-1"
                      @click="openImageInNewTab(selectedOrder.payment_proof)"
                    >
                      <VIcon
                        icon="tabler-zoom-in"
                        size="14"
                      /> Klik untuk memperbesar
                    </div>
                  </div>
                  <div
                    v-else
                    class="text-disabled py-4"
                  >
                    <VIcon
                      icon="tabler-photo-off"
                      size="30"
                      class="mb-2"
                    />
                    <div>Belum ada bukti upload</div>
                  </div>
                </VCardText>
              </VCard>

              <div class="mt-6">
                <h5 class="text-subtitle-2 text-disabled mb-3 text-uppercase">
                  Tindakan
                </h5>

                <div v-if="selectedOrder.status === 'pending' && selectedOrder.payment_method === 'QRIS' && selectedOrder.payment_status === 'unpaid'">
                  <VAlert
                    type="warning"
                    variant="tonal"
                    class="mb-3 text-caption"
                    density="compact"
                  >
                    Cek bukti transfer sebelum memproses.
                  </VAlert>
                  <VBtn
                    block
                    color="success"
                    size="large"
                    :disabled="!selectedOrder.payment_proof"
                    :loading="isSubmitLoading"
                    @click="triggerVerifyPayment"
                  >
                    <VIcon
                      start
                      icon="tabler-circle-check"
                    /> Verifikasi Pembayaran
                  </VBtn>
                </div>

                <div v-else-if="selectedOrder.status === 'pending'">
                  <VAlert
                    type="info"
                    variant="tonal"
                    class="mb-3 text-caption"
                    density="compact"
                  >
                    Pesanan baru (COD/Lunas). Cek stok dapur.
                  </VAlert>
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
                  <VSelect
                    v-model="selectedCourierId"
                    :items="courierStore.couriers.filter(c => c.courier_status === 'available')"
                    item-title="name"
                    item-value="id"
                    label="Pilih Kurir"
                    variant="outlined"
                    bg-color="white"
                    density="compact"
                    class="mb-3"
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
        <VIcon :icon="snackbar.icon" /><span class="font-weight-medium">{{ snackbar.text }}</span>
      </div>
    </VSnackbar>
  </div>
</template>
