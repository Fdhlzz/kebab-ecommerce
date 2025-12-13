<script setup>
import { useCategoryStore } from '@/stores/category'
import { onMounted, reactive, ref } from 'vue'

const categoryStore = useCategoryStore()

// UI States
const isDialogVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const isSubmitLoading = ref(false)

// Form Data
const defaultForm = {
  id: null,
  name: '',
  image: null,
  imageUrl: null, // For preview
}

const form = reactive({ ...defaultForm })
const deleteId = ref(null)

// Fetch Data on Load
onMounted(() => {
  categoryStore.fetchCategories()
})

// --- Actions ---

const openDialog = (category = null) => {
  if (category) {
    // Edit Mode
    form.id = category.id
    form.name = category.name
    form.imageUrl = category.image ? `/storage/${category.image}` : null
    form.image = null // Reset file input
  } else {
    // Create Mode
    Object.assign(form, defaultForm)
  }
  isDialogVisible.value = true
}

const handleImageSelect = event => {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    form.imageUrl = URL.createObjectURL(file)
  }
}

const handleSubmit = async () => {
  isSubmitLoading.value = true
  
  let result
  if (form.id) {
    result = await categoryStore.updateCategory(form.id, form)
  } else {
    result = await categoryStore.createCategory(form)
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
  await categoryStore.deleteCategory(deleteId.value)
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
          Kategori Menu
        </h2>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openDialog"
        >
          Tambah Kategori
        </VBtn>
      </VCol>
    </VRow>

    <VCard>
      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th class="text-uppercase">
              Icon
            </th>
            <th class="text-uppercase">
              Nama Kategori
            </th>
            <th class="text-uppercase">
              Slug
            </th>
            <th class="text-uppercase text-center">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in categoryStore.categories"
            :key="item.id"
          >
            <td>
              <VAvatar
                variant="tonal"
                rounded
                size="40"
              >
                <VImg
                  v-if="item.image"
                  :src="`/storage/${item.image}`"
                />
                <VIcon
                  v-else
                  icon="tabler-category"
                />
              </VAvatar>
            </td>
            <td class="font-weight-medium">
              {{ item.name }}
            </td>
            <td class="text-disabled">
              {{ item.slug }}
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
          
          <tr v-if="categoryStore.categories.length === 0">
            <td
              colspan="4"
              class="text-center pa-4 text-disabled"
            >
              Belum ada kategori data.
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <VDialog
      v-model="isDialogVisible"
      max-width="500"
    >
      <VCard :title="form.id ? 'Edit Kategori' : 'Tambah Kategori'">
        <VCardText>
          <VForm @submit.prevent="handleSubmit">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  label="Nama Kategori"
                  placeholder="Contoh: Kebab Frozen"
                  variant="outlined"
                  required
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    size="60"
                    variant="outlined"
                    rounded
                  >
                    <VImg
                      v-if="form.imageUrl"
                      :src="form.imageUrl"
                      cover
                    />
                    <VIcon
                      v-else
                      icon="tabler-photo"
                    />
                  </VAvatar>
                  <VFileInput
                    label="Upload Icon"
                    accept="image/*"
                    prepend-icon=""
                    prepend-inner-icon="tabler-camera"
                    variant="outlined"
                    hide-details
                    @change="handleImageSelect"
                  />
                </div>
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
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isDeleteDialogVisible"
      max-width="400"
    >
      <VCard title="Hapus Kategori?">
        <VCardText>
          Apakah Anda yakin ingin menghapus kategori ini? Semua produk di dalamnya mungkin akan terpengaruh.
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
