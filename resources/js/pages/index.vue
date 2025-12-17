<script setup>
import { useOrderStore } from '@/stores/order'
import { computed, onMounted, ref } from 'vue'

// Access Stores
const orderStore = useOrderStore()

// State
const isLoading = ref(true)

// --- Helpers ---
const formatRupiah = val => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)
}

const formatDate = dateString => {
  const options = { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }
  
  return new Date(dateString).toLocaleDateString('id-ID', options)
}

// --- Computed Metrics (Client-Side Calculation) ---
// Note: In a large app, these should come from a specific API endpoint like /api/dashboard-stats

const totalRevenue = computed(() => {
  return orderStore.orders
    .filter(o => o.status === 'completed' && o.payment_status === 'paid')
    .reduce((sum, order) => sum + Number(order.total_price) + Number(order.shipping_cost), 0)
})

const pendingCount = computed(() => orderStore.orders.filter(o => o.status === 'pending').length)
const deliveryCount = computed(() => orderStore.orders.filter(o => o.status === 'on_delivery').length)
const completedCount = computed(() => orderStore.orders.filter(o => o.status === 'completed').length)

const recentOrders = computed(() => {
  // Sort by ID desc and take top 5
  return [...orderStore.orders].sort((a, b) => b.id - a.id).slice(0, 5)
})

// Calculate Top Products based on Order Items
const topProducts = computed(() => {
  const productMap = {}
  
  orderStore.orders.forEach(order => {
    // Only count active/completed orders
    if (order.status !== 'cancelled') {
      order.items.forEach(item => {
        if (!productMap[item.product.name]) {
          productMap[item.product.name] = { 
            name: item.product.name, 
            sales: 0, 
            revenue: 0,
            image: item.product.image, // Assuming product has image
          }
        }
        productMap[item.product.name].sales += item.quantity
        productMap[item.product.name].revenue += (item.price * item.quantity)
      })
    }
  })

  // Convert to array and sort by sales
  return Object.values(productMap).sort((a, b) => b.sales - a.sales).slice(0, 5)
})

// --- Lifecycle ---
onMounted(async () => {
  isLoading.value = true
  await orderStore.fetchOrders({ status: 'all' }) // Fetch everything to calculate stats
  isLoading.value = false
})

// UI Config
const getStatusColor = status => {
  const map = {
    pending: 'warning',
    processing: 'info',
    // eslint-disable-next-line camelcase
    on_delivery: 'primary',
    completed: 'success',
    cancelled: 'error',
  }
  
  return map[status] || 'grey'
}
</script>

<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          Dashboard
        </h2>
        <p class="text-body-1 text-medium-emphasis">
          Halo Admin! Inilah ringkasan bisnis Kebab Anda hari ini.
        </p>
      </div>
      <VBtn 
        color="primary" 
        variant="tonal" 
        prepend-icon="tabler-refresh" 
        :loading="isLoading"
        @click="orderStore.fetchOrders({ status: 'all' })"
      >
        Refresh Data
      </VBtn>
    </div>

    <VRow class="mb-6">
      <VCol
        cols="12"
        sm="6"
        md="3"
      >
        <VCard
          elevation="2"
          class="h-100"
        >
          <VCardText class="d-flex justify-space-between align-start">
            <div>
              <p class="text-subtitle-2 text-disabled mb-1">
                Total Pendapatan
              </p>
              <h4 class="text-h4 font-weight-bold text-success">
                {{ formatRupiah(totalRevenue) }}
              </h4>
            </div>
            <VAvatar
              color="success"
              variant="tonal"
              rounded
              size="42"
            >
              <VIcon
                icon="tabler-wallet"
                size="26"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        sm="6"
        md="3"
      >
        <VCard
          elevation="2"
          class="h-100"
        >
          <VCardText class="d-flex justify-space-between align-start">
            <div>
              <p class="text-subtitle-2 text-disabled mb-1">
                Perlu Diproses
              </p>
              <h4 class="text-h4 font-weight-bold text-warning">
                {{ pendingCount }}
              </h4>
              <small
                v-if="pendingCount > 0"
                class="text-warning font-weight-bold"
              >
                Butuh tindakan segera!
              </small>
            </div>
            <VAvatar
              color="warning"
              variant="tonal"
              rounded
              size="42"
            >
              <VIcon
                icon="tabler-bell-ringing"
                size="26"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        sm="6"
        md="3"
      >
        <VCard
          elevation="2"
          class="h-100"
        >
          <VCardText class="d-flex justify-space-between align-start">
            <div>
              <p class="text-subtitle-2 text-disabled mb-1">
                Sedang Diantar
              </p>
              <h4 class="text-h4 font-weight-bold text-primary">
                {{ deliveryCount }}
              </h4>
            </div>
            <VAvatar
              color="primary"
              variant="tonal"
              rounded
              size="42"
            >
              <VIcon
                icon="tabler-moped"
                size="26"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        sm="6"
        md="3"
      >
        <VCard
          elevation="2"
          class="h-100"
        >
          <VCardText class="d-flex justify-space-between align-start">
            <div>
              <p class="text-subtitle-2 text-disabled mb-1">
                Pesanan Selesai
              </p>
              <h4 class="text-h4 font-weight-bold text-info">
                {{ completedCount }}
              </h4>
            </div>
            <VAvatar
              color="info"
              variant="tonal"
              rounded
              size="42"
            >
              <VIcon
                icon="tabler-checkbox"
                size="26"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        cols="12"
        md="8"
      >
        <VCard
          title="Pesanan Terbaru"
          class="h-100"
        >
          <template #append>
            <RouterLink
              to="/orders"
              class="text-decoration-none text-sm font-weight-medium text-primary"
            >
              Lihat Semua
            </RouterLink>
          </template>
          
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-uppercase text-caption font-weight-bold">
                  ID & Pelanggan
                </th>
                <th class="text-uppercase text-caption font-weight-bold">
                  Status
                </th>
                <th class="text-uppercase text-caption font-weight-bold text-end">
                  Total
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="order in recentOrders"
                :key="order.id"
              >
                <td class="py-3">
                  <div class="d-flex flex-column">
                    <span class="font-weight-bold text-body-2">#{{ order.id }} - {{ order.customer_name }}</span>
                    <span class="text-caption text-disabled">{{ formatDate(order.created_at) }}</span>
                  </div>
                </td>
                <td>
                  <VChip
                    :color="getStatusColor(order.status)"
                    size="x-small"
                    label
                    class="text-capitalize font-weight-bold"
                  >
                    {{ order.status.replace('_', ' ') }}
                  </VChip>
                </td>
                <td class="text-end font-weight-medium">
                  {{ formatRupiah(Number(order.total_price) + Number(order.shipping_cost)) }}
                </td>
              </tr>
              <tr v-if="recentOrders.length === 0">
                <td
                  colspan="3"
                  class="text-center text-disabled py-4"
                >
                  Belum ada data.
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <VCard
          title="Menu Terlaris 🥙"
          class="h-100"
        >
          <VCardText>
            <VList class="pa-0">
              <VListItem
                v-for="(product, index) in topProducts"
                :key="product.name"
                class="px-0 mb-2"
              >
                <template #prepend>
                  <VAvatar
                    color="grey-lighten-4"
                    rounded
                    size="40"
                    class="me-3 border"
                  >
                    <VImg
                      v-if="product.image"
                      :src="product.image"
                      cover
                    />
                    <span
                      v-else
                      class="font-weight-bold"
                    >{{ index + 1 }}</span>
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-bold text-body-2">
                  {{ product.name }}
                </VListItemTitle>
                <VListItemSubtitle class="text-caption mt-1">
                  {{ product.sales }} terjual
                </VListItemSubtitle>

                <template #append>
                  <div class="text-end">
                    <span class="text-success font-weight-bold text-caption">
                      {{ formatRupiah(product.revenue) }}
                    </span>
                  </div>
                </template>
              </VListItem>
              
              <div
                v-if="topProducts.length === 0"
                class="text-center text-disabled py-4"
              >
                Belum ada data penjualan.
              </div>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
