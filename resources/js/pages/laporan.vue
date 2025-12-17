<script setup>
import { useOrderStore } from '@/stores/order'
import { computed, onMounted, reactive, ref } from 'vue'

const orderStore = useOrderStore()

// --- Configuration ---
const companyInfo = reactive({
  name: 'Kebab Turki Dashboard',
  address: 'Jl. Hertasning No. 88, Makassar, Sulawesi Selatan',
  phone: '(0411) 456-7890',
  email: 'finance@kebabturki.com',
})

// --- State ---
const currentDate = new Date()
const selectedMonth = ref(currentDate.getMonth())
const selectedYear = ref(currentDate.getFullYear())

const months = [
  { title: 'Januari', value: 0 },
  { title: 'Februari', value: 1 },
  { title: 'Maret', value: 2 },
  { title: 'April', value: 3 },
  { title: 'Mei', value: 4 },
  { title: 'Juni', value: 5 },
  { title: 'Juli', value: 6 },
  { title: 'Agustus', value: 7 },
  { title: 'September', value: 8 },
  { title: 'Oktober', value: 9 },
  { title: 'November', value: 10 },
  { title: 'Desember', value: 11 },
]

const years = Array.from({ length: 5 }, (_, i) => currentDate.getFullYear() - i)

// --- Helpers ---
const formatRupiah = val => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)

const formatDate = dateString => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  })
}

// --- Logic ---
onMounted(() => {
  orderStore.fetchOrders({ status: 'all' })
})

const filteredOrders = computed(() => {
  return orderStore.orders.filter(order => {
    const d = new Date(order.created_at)
    
    return d.getMonth() === selectedMonth.value &&
           d.getFullYear() === selectedYear.value &&
           order.status === 'completed'
  }).sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const reportSummary = computed(() => {
  const totalRev = filteredOrders.value.reduce((sum, order) => {
    const amount = order.grand_total ? Number(order.grand_total) : (Number(order.total_price) + Number(order.shipping_cost))
    
    return sum + amount
  }, 0)

  return {
    totalRevenue: totalRev,
    totalTx: filteredOrders.value.length,
    qrisCount: filteredOrders.value.filter(o => o.payment_method === 'QRIS').length,
    codCount: filteredOrders.value.filter(o => o.payment_method === 'COD').length,
  }
})

const handlePrint = () => {
  window.print()
}
</script>

<template>
  <div>
    <div class="screen-view no-print">
      <div class="d-flex flex-wrap justify-space-between align-center mb-6">
        <div>
          <h2 class="text-h4 font-weight-bold">
            Laporan Keuangan
          </h2>
          <p class="text-body-1 text-medium-emphasis">
            Analisis pendapatan dan rekapitulasi transaksi bulanan.
          </p>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-refresh"
            @click="orderStore.fetchOrders({ status: 'all' })"
          >
            Refresh Data
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="tabler-printer"
            @click="handlePrint"
          >
            Cetak Laporan
          </VBtn>
        </div>
      </div>

      <VCard
        elevation="0"
        border
        class="mb-6"
      >
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="3"
            >
              <VSelect
                v-model="selectedMonth"
                :items="months"
                label="Pilih Bulan"
                variant="outlined"
                density="compact"
                hide-details
                prepend-inner-icon="tabler-calendar-month"
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <VSelect
                v-model="selectedYear"
                :items="years"
                label="Pilih Tahun"
                variant="outlined"
                density="compact"
                hide-details
                prepend-inner-icon="tabler-calendar"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <VRow class="mb-6">
        <VCol
          cols="12"
          md="4"
        >
          <VCard
            elevation="0"
            border
            class="h-100"
          >
            <VCardText class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-1">
                  Total Pendapatan
                </p>
                <h4 class="text-h4 font-weight-bold text-success">
                  {{ formatRupiah(reportSummary.totalRevenue) }}
                </h4>
              </div>
              <VAvatar
                color="success"
                variant="tonal"
                rounded
                size="48"
              >
                <VIcon
                  icon="tabler-wallet"
                  size="28"
                />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          md="4"
        >
          <VCard
            elevation="0"
            border
            class="h-100"
          >
            <VCardText class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-1">
                  Total Transaksi
                </p>
                <h4 class="text-h4 font-weight-bold text-primary">
                  {{ reportSummary.totalTx }}
                  <span class="text-body-2 text-disabled font-weight-regular">Pesanan</span>
                </h4>
              </div>
              <VAvatar
                color="primary"
                variant="tonal"
                rounded
                size="48"
              >
                <VIcon
                  icon="tabler-shopping-cart"
                  size="28"
                />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          md="4"
        >
          <VCard
            elevation="0"
            border
            class="h-100"
          >
            <VCardText>
              <p class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2">
                Metode Pembayaran
              </p>
              <div class="d-flex gap-4">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-qrcode"
                    color="purple"
                    size="20"
                  />
                  <div>
                    <div class="font-weight-bold">
                      {{ reportSummary.qrisCount }}
                    </div>
                    <div class="text-caption">
                      QRIS
                    </div>
                  </div>
                </div>
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-cash"
                    color="orange"
                    size="20"
                  />
                  <div>
                    <div class="font-weight-bold">
                      {{ reportSummary.codCount }}
                    </div>
                    <div class="text-caption">
                      COD
                    </div>
                  </div>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <VCard title="Rincian Transaksi">
        <VTable hover>
          <thead>
            <tr>
              <th class="text-uppercase text-caption font-weight-bold">
                Tanggal
              </th>
              <th class="text-uppercase text-caption font-weight-bold">
                Order ID
              </th>
              <th class="text-uppercase text-caption font-weight-bold">
                Pelanggan
              </th>
              <th class="text-uppercase text-caption font-weight-bold text-center">
                Metode
              </th>
              <th class="text-uppercase text-caption font-weight-bold text-end">
                Total
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in filteredOrders"
              :key="order.id"
            >
              <td class="text-body-2">
                {{ formatDate(order.created_at) }}
              </td>
              <td>
                <VChip
                  size="small"
                  label
                >
                  #{{ order.id }}
                </VChip>
              </td>
              <td class="font-weight-medium">
                {{ order.customer_name }}
              </td>
              <td class="text-center">
                <VChip
                  size="x-small"
                  variant="tonal"
                  :color="order.payment_method === 'QRIS' ? 'purple' : 'orange'"
                >
                  {{ order.payment_method }}
                </VChip>
              </td>
              <td class="text-end font-weight-bold">
                {{ formatRupiah(Number(order.total_price) + Number(order.shipping_cost)) }}
              </td>
            </tr>
            <tr v-if="filteredOrders.length === 0">
              <td
                colspan="5"
                class="text-center py-8 text-disabled"
              >
                Tidak ada data pada periode ini.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </div>

    <div class="print-view-only">
      <header class="print-header">
        <div class="header-content">
          <div class="company-logo">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="40"
              height="40"
              viewBox="0 0 24 24"
              fill="none"
              stroke="black"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M12 3c1.918 0 3.52 1.35 3.91 3.151a4 4 0 0 1 2.09 7.723L18 15v4h-1v2H7v-2H6v-4l.009-1.126a4 4 0 0 1 2.091-7.723A4 4 0 0 1 12 3Z" />
              <path d="M6.18 17h11.64" />
            </svg>
          </div>
          <div class="company-details">
            <h1>{{ companyInfo.name }}</h1>
            <p>{{ companyInfo.address }}</p>
            <p>{{ companyInfo.phone }} | {{ companyInfo.email }}</p>
          </div>
        </div>
        <div class="report-meta">
          <h2>LAPORAN PENJUALAN</h2>
          <p>Periode: {{ months[selectedMonth].title }} {{ selectedYear }}</p>
        </div>
        <div class="header-line" />
      </header>

      <div class="print-summary">
        <div class="summary-box">
          <span>Total Pendapatan</span>
          <strong>{{ formatRupiah(reportSummary.totalRevenue) }}</strong>
        </div>
        <div class="summary-box">
          <span>Total Transaksi</span>
          <strong>{{ reportSummary.totalTx }} Order</strong>
        </div>
      </div>

      <table class="print-table">
        <thead>
          <tr>
            <th style="inline-size: 5%;">
              No
            </th>
            <th style="inline-size: 15%;">
              Tanggal
            </th>
            <th style="inline-size: 30%;">
              Pelanggan
            </th>
            <th style="inline-size: 15%;">
              Metode
            </th>
            <th
              style="inline-size: 25%;"
              class="text-right"
            >
              Total
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(order, index) in filteredOrders"
            :key="order.id"
          >
            <td>{{ index + 1 }}</td>
            <td>{{ formatDate(order.created_at) }}</td>
            <td>
              {{ order.customer_name }}
              <br>
              <small style="color: #666;">ID: #{{ order.id }}</small>
            </td>
            <td>{{ order.payment_method }}</td>
            <td class="text-right">
              {{ formatRupiah(Number(order.total_price) + Number(order.shipping_cost)) }}
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td
              colspan="4"
              class="text-right bold"
            >
              GRAND TOTAL
            </td>
            <td class="text-right bold">
              {{ formatRupiah(reportSummary.totalRevenue) }}
            </td>
          </tr>
        </tfoot>
      </table>

      <div class="print-footer">
        <div class="signature-box">
          <p>Makassar, {{ new Date().toLocaleDateString('id-ID') }}</p>
          <p>Dibuat Oleh,</p>
          <div class="sign-space" />
          <p class="bold">
            Admin Keuangan
          </p>
        </div>
        <div class="signature-box">
          <p>&nbsp;</p>
          <p>Mengetahui,</p>
          <div class="sign-space" />
          <p class="bold">
            Manager Operasional
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ------------------------------------------- */

/* 1. SCREEN STYLES (Modern Dashboard Look)    */

/* ------------------------------------------- */
.screen-view {
  display: block;
}

.print-view-only {
  display: none; /* Hidden by default */
}

/* ------------------------------------------- */

/* 2. PRINT STYLES (Formal Document Look)      */

/* ------------------------------------------- */
@media print {
  /* HIDE EVERYTHING ELSE */
  .no-print,
  .v-navigation-drawer,
  .v-app-bar,
  .layout-navbar,
  .v-footer,
  .screen-view {
    display: none !important;
  }

  /* SHOW PRINT VIEW */
  .print-view-only {
    display: block !important;
    background: white;
    color: black;
    font-family: "Times New Roman", serif;
    inline-size: 100%;
  }

  /* PAGE SETUP */
  @page {
    margin: 1.5cm;
    size: a4;
  }

  body {
    background: white !important;
  }

  /* HEADER */
  .print-header {
    margin-block-end: 20px;
  }

  .header-content {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-block-end: 15px;
  }

  .company-logo {
    padding: 5px;
    border: 2px solid black;
    border-radius: 5px;
  }

  .company-details h1 {
    margin: 0;
    font-size: 18pt;
    font-weight: bold;
    text-transform: uppercase;
  }

  .company-details p {
    font-size: 10pt;
    margin-block: 2px;
    margin-inline: 0;
  }

  .report-meta {
    margin-block-start: -50px; /* Pull up next to logo if needed, or adjust */
    text-align: end;
  }

  .report-meta h2 {
    margin: 0;
    font-size: 14pt;
    font-weight: bold;
  }

  .header-line {
    border-block-end: 2px solid black;
    margin-block-start: 10px;
  }

  /* SUMMARY */
  .print-summary {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    border: 1px solid black;
    margin-block-end: 20px;
  }

  .summary-box {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
  }

  .summary-box span {
    font-size: 10pt;
    text-transform: uppercase;
  }

  .summary-box strong {
    font-size: 12pt;
  }

  /* TABLE */
  .print-table {
    border-collapse: collapse;
    font-size: 10pt;
    inline-size: 100%;
    margin-block-end: 30px;
  }

  .print-table th {
    padding: 8px;
    border-block-end: 1px solid black;
    border-block-start: 1px solid black;
    font-weight: bold;
    text-align: start;
    text-transform: uppercase;
  }

  .print-table td {
    border-block-end: 1px solid #ddd;
    padding-block: 6px;
    padding-inline: 8px;
  }

  .print-table .text-right {
    text-align: end;
  }

  .print-table tfoot td {
    border-block-end: 2px solid black;
    border-block-start: 2px solid black;
    font-weight: bold;
    padding-block: 10px;
    padding-inline: 8px;
  }

  /* FOOTER / SIGNATURES */
  .print-footer {
    display: flex;
    justify-content: space-between;
    margin-block-start: 50px;
    page-break-inside: avoid;
  }

  .signature-box {
    inline-size: 200px;
    text-align: center;
  }

  .sign-space {
    block-size: 60px;
  }

  .bold {
    font-weight: bold;
  }
}
</style>
