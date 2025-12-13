<script setup>
import { useShippingStore } from '@/stores/shipping'
import { onMounted, reactive, ref, watch } from 'vue'

const shippingStore = useShippingStore()

// UI State
const search = ref('')
const isDialogVisible = ref(false)
const isSubmitLoading = ref(false)

// Edit Form Data (Keep using camelCase here for JS consistency)
const form = reactive({
  regionCode: '',
  regionName: '',
  price: 0,
})

// Fetch Data
const loadData = (page = 1) => {
  shippingStore.fetchRates({
    page: page,
    search: search.value,
  })
}

onMounted(() => {
  loadData()
})

watch(search, () => {
  loadData(1)
})

const formatRupiah = value => {
  if (value === null || value === undefined) return '-'
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

// --- Actions ---

const openEditDialog = item => {
  if (!item.code) {
    console.error("Item does not have a code:", item)
    alert("Error: Kode wilayah tidak ditemukan. Silakan refresh halaman.")
    
    return
  }

  // Assign values to form
  form.regionCode = item.code
  form.regionName = item.name
  form.price = item.price ? parseFloat(item.price) : 0
  
  isDialogVisible.value = true
}

const handleSave = async () => {
  // Validation
  if (!form.regionCode) {
    alert("Error: Kode wilayah kosong.")
    
    return
  }

  isSubmitLoading.value = true
  
  const result = await shippingStore.updateRate({
    // eslint-disable-next-line camelcase
    region_code: form.regionCode,
    price: form.price,
  })

  isSubmitLoading.value = false

  if (result.success) {
    isDialogVisible.value = false
  } else {
    alert(result.message)
  }
}

const handleReset = async item => {
  if (confirm(`Reset ongkir untuk Kecamatan ${item.name} ke default?`)) {
    await shippingStore.resetRate(item.code)
  }
}
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol cols="12">
        <h2 class="text-h4 font-weight-bold">
          Pengaturan Ongkir
        </h2>
        <p class="text-subtitle-2 text-disabled">
          Atur biaya pengiriman berdasarkan Kecamatan (District).
        </p>
      </VCol>
    </VRow>

    <VCard>
      <VCardText class="d-flex align-center py-4 gap-4">
        <VTextField
          v-model="search"
          density="compact"
          label="Cari Kecamatan (Contoh: Biringkanaya)"
          prepend-inner-icon="tabler-search"
          variant="outlined"
          hide-details
          class="flex-grow-0 w-50"
        />
        <VSpacer />
      </VCardText>

      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th class="text-uppercase">
              Kode
            </th>
            <th class="text-uppercase">
              Wilayah (Kecamatan)
            </th>
            <th class="text-uppercase">
              Biaya Ongkir
            </th>
            <th class="text-uppercase text-center">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in shippingStore.rates"
            :key="item.code"
          >
            <td class="text-disabled">
              {{ item.code }}
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="font-weight-medium text-uppercase">
                  KEC. {{ item.name }}
                </span>
                <span class="text-caption text-disabled">
                  {{ item.city_name }}
                </span>
              </div>
            </td>
            <td>
              <VChip 
                :color="item.price !== null ? 'success' : 'secondary'" 
                variant="tonal"
                size="small"
              >
                {{ item.price !== null ? formatRupiah(item.price) : 'Belum Diatur' }}
              </VChip>
            </td>
            <td class="text-center">
              <VBtn 
                icon 
                variant="text" 
                color="primary" 
                size="small" 
                @click="openEditDialog(item)"
              >
                <VIcon
                  icon="tabler-edit"
                  size="22"
                />
              </VBtn>
              
              <VBtn 
                v-if="item.price !== null"
                icon 
                variant="text" 
                color="error" 
                size="small" 
                title="Reset ke Default"
                @click="handleReset(item)"
              >
                <VIcon
                  icon="tabler-rotate-clockwise"
                  size="22"
                />
              </VBtn>
            </td>
          </tr>
          
          <tr v-if="shippingStore.rates.length === 0">
            <td
              colspan="4"
              class="text-center pa-4 text-disabled"
            >
              Data kecamatan tidak ditemukan.
            </td>
          </tr>
        </tbody>
      </VTable>

      <VCardText class="d-flex justify-end pt-2">
        <VPagination
          v-model="shippingStore.pagination.current_page"
          :length="shippingStore.pagination.last_page"
          :total-visible="5"
          size="small"
          @update:model-value="loadData"
        />
      </VCardText>
    </VCard>

    <VDialog
      v-model="isDialogVisible"
      max-width="400"
    >
      <VCard :title="`Atur Ongkir: ${form.region_name}`">
        <VCardText>
          <VForm @submit.prevent="handleSave">
            <VTextField
              v-model="form.price"
              label="Biaya Ongkir (Rp)"
              type="number"
              prefix="Rp"
              variant="outlined"
              autofocus
              hint="Masukkan 0 untuk Gratis Ongkir"
              persistent-hint
            />
          </VForm>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            :loading="isSubmitLoading"
            @click="handleSave"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
