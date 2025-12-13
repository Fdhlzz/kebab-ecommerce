<script setup>
import { useCategoryStore } from '@/stores/category'
import { useProductStore } from '@/stores/product'
import { onMounted, reactive, ref } from 'vue'

const productStore = useProductStore()
const categoryStore = useCategoryStore()

// UI States
const isDialogVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const isSubmitLoading = ref(false)
const search = ref('')

// Form
const defaultForm = {
  id: null,
  name: '',
  categoryId: null,
  description: '',
  price: 0,
  stock: 0,
  isActive: true,
  images: [], // For file upload
  existingImages: [], // For preview
}

const form = reactive({ ...defaultForm })
const deleteId = ref(null)

onMounted(async () => {
  await Promise.all([
    productStore.fetchProducts(),
    categoryStore.fetchCategories(),
  ])
})

// Helper for Rupiah formatting
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const openDialog = (product = null) => {
  if (product) {
    form.id = product.id
    form.name = product.name
    form.categoryId = product.category_id
    form.description = product.description
    form.price = product.price
    form.stock = product.stock
    form.isActive = !!product.is_active
    form.existingImages = product.images || []
    form.images = []
  } else {
    Object.assign(form, defaultForm)
    form.existingImages = []
    form.images = []
  }
  isDialogVisible.value = true
}

const handleFileChange = e => {
  const files = Array.from(e.target.files)

  form.images = files
}

const handleSubmit = async () => {
  isSubmitLoading.value = true
  
  let result
  if (form.id) {
    result = await productStore.updateProduct(form.id, form)
  } else {
    result = await productStore.createProduct(form)
  }

  isSubmitLoading.value = false

  if (result.success) {
    isDialogVisible.value = false
  } else {
    alert(result.message)
  }
}

const confirmDelete = id => {
  deleteId.value = id
  isDeleteDialogVisible.value = true
}

const handleDelete = async () => {
  await productStore.deleteProduct(deleteId.value)
  isDeleteDialogVisible.value = false
}
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol
        cols="12"
        class="d-flex justify-space-between align-center"
      >
        <h2 class="text-h4 font-weight-bold">
          Produk Menu
        </h2>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openDialog"
        >
          Tambah Menu
        </VBtn>
      </VCol>
    </VRow>

    <VCard>
      <VCardText class="d-flex align-center py-4 gap-4">
        <VTextField
          v-model="search"
          density="compact"
          label="Cari Menu..."
          prepend-inner-icon="tabler-search"
          variant="outlined"
          hide-details
          class="flex-grow-0 w-25"
          @update:model-value="productStore.fetchProducts({ search: $event })"
        />
      </VCardText>

      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th class="text-uppercase">
              Menu
            </th>
            <th class="text-uppercase">
              Kategori
            </th>
            <th class="text-uppercase">
              Harga
            </th>
            <th class="text-uppercase text-center">
              Stok
            </th>
            <th class="text-uppercase text-center">
              Status
            </th>
            <th class="text-uppercase text-center">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in productStore.products"
            :key="item.id"
          >
            <td>
              <div class="d-flex align-center">
                <VAvatar
                  rounded
                  variant="tonal"
                  size="40"
                  class="me-3"
                >
                  <VImg 
                    v-if="item.images && item.images.length > 0" 
                    :src="`/storage/${item.images[0].image_path}`" 
                    cover
                  />
                  <VIcon
                    v-else
                    icon="tabler-meat"
                  />
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">{{ item.name }}</span>
                </div>
              </div>
            </td>
            <td>
              <VChip
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ item.category?.name || '-' }}
              </VChip>
            </td>
            <td class="font-weight-medium text-success">
              {{ formatRupiah(item.price) }}
            </td>
            <td class="text-center">
              <span :class="item.stock < 5 ? 'text-error font-weight-bold' : ''">
                {{ item.stock }}
              </span>
            </td>
            <td class="text-center">
              <VChip
                size="small"
                :color="item.is_active ? 'success' : 'secondary'"
              >
                {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
              </VChip>
            </td>
            <td class="text-center">
              <VBtn
                icon
                variant="text"
                color="default"
                size="small"
                @click="openDialog(item)"
              >
                <VIcon
                  icon="tabler-edit"
                  size="22"
                />
              </VBtn>
              <VBtn
                icon
                variant="text"
                color="error"
                size="small"
                @click="confirmDelete(item.id)"
              >
                <VIcon
                  icon="tabler-trash"
                  size="22"
                />
              </VBtn>
            </td>
          </tr>
          <tr v-if="productStore.products.length === 0">
            <td
              colspan="6"
              class="text-center pa-4 text-disabled"
            >
              Tidak ada data produk.
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <VDialog
      v-model="isDialogVisible"
      max-width="700"
    >
      <VCard :title="form.id ? 'Edit Menu' : 'Tambah Menu Baru'">
        <VCardText>
          <VForm @submit.prevent="handleSubmit">
            <VRow>
              <VCol
                cols="12"
                md="8"
              >
                <VTextField
                  v-model="form.name"
                  label="Nama Menu"
                  placeholder="Contoh: Kebab Daging Sapi Besar"
                  variant="outlined"
                />
              </VCol>
              
              <VCol
                cols="12"
                md="4"
              >
                <VSelect
                  v-model="form.category_id"
                  :items="categoryStore.categories"
                  item-title="name"
                  item-value="id"
                  label="Kategori"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="form.price"
                  label="Harga (Rp)"
                  type="number"
                  prefix="Rp"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="form.stock"
                  label="Stok Harian"
                  type="number"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="form.description"
                  label="Deskripsi"
                  rows="3"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  label="Upload Foto (Bisa banyak)"
                  multiple
                  accept="image/*"
                  prepend-icon="tabler-camera"
                  variant="outlined"
                  @change="handleFileChange"
                />
                
                <div
                  v-if="form.existingImages.length"
                  class="d-flex gap-2 mt-2 overflow-x-auto"
                >
                  <VAvatar 
                    v-for="img in form.existingImages" 
                    :key="img.id" 
                    size="60" 
                    rounded
                    class="border"
                  >
                    <VImg
                      :src="`/storage/${img.image_path}`"
                      cover
                    />
                  </VAvatar>
                </div>
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="form.is_active"
                  label="Status Aktif"
                  color="success"
                  hide-details
                />
              </VCol>
            </VRow>
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
            @click="handleSubmit"
          >
            Simpan Menu
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isDeleteDialogVisible"
      max-width="400"
    >
      <VCard title="Hapus Menu?">
        <VCardText>
          Apakah Anda yakin ingin menghapus menu ini? Data tidak bisa dikembalikan.
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="text"
            @click="isDeleteDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            @click="handleDelete"
          >
            Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
