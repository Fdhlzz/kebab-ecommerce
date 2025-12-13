<script setup>
import { useCourierStore } from '@/stores/courier'
import { onMounted, reactive, ref } from 'vue'

const courierStore = useCourierStore()

// UI State
const isDialogVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const isSubmitLoading = ref(false)

// Form
const defaultForm = {
  id: null,
  name: '',
  email: '',
  password: '',

  // We don't edit status here manually usually, 
  // status changes when they take an order.
}

const form = reactive({ ...defaultForm })
const deleteId = ref(null)

onMounted(() => {
  courierStore.fetchCouriers()
})

// --- Status Helpers ---
const resolveStatusColor = status => {
  if (status === 'available') return 'success' // Green
  if (status === 'busy') return 'warning'      // Orange
  
  return 'secondary'                           // Grey (Offline)
}

const resolveStatusText = status => {
  if (status === 'available') return 'Tersedia'
  if (status === 'busy') return 'Sedang Mengantar'
  
  return 'Offline'
}

// --- Actions ---
const openDialog = (user = null) => {
  if (user) {
    form.id = user.id
    form.name = user.name
    form.email = user.email
    form.password = '' 
  } else {
    Object.assign(form, defaultForm)
  }
  isDialogVisible.value = true
}

const handleSubmit = async () => {
  isSubmitLoading.value = true
  let result
  if (form.id) {
    result = await courierStore.updateCourier(form.id, form)
  } else {
    result = await courierStore.createCourier(form)
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
  await courierStore.deleteCourier(deleteId.value)
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
        <div>
          <h2 class="text-h4 font-weight-bold">
            Manajemen Kurir
          </h2>
          <p class="text-subtitle-2 text-disabled">
            Buat akun untuk petugas pengiriman
          </p>
        </div>
        <VBtn
          color="primary"
          prepend-icon="tabler-user-plus"
          @click="openDialog"
        >
          Tambah Kurir
        </VBtn>
      </VCol>
    </VRow>

    <VCard>
      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th class="text-uppercase">
              Nama
            </th>
            <th class="text-uppercase">
              Email
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
            v-for="item in courierStore.couriers"
            :key="item.id"
          >
            <td class="font-weight-medium">
              <div class="d-flex align-center gap-2">
                <VAvatar
                  color="info"
                  variant="tonal"
                  size="34"
                >
                  <VIcon
                    icon="tabler-truck-delivery"
                    size="20"
                  />
                </VAvatar>
                {{ item.name }}
              </div>
            </td>
            <td>{{ item.email }}</td>
            
            <td class="text-center">
              <VChip 
                :color="resolveStatusColor(item.courier_status)" 
                size="small" 
                class="font-weight-medium"
              >
                {{ resolveStatusText(item.courier_status) }}
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
                :disabled="item.courier_status === 'busy'"
                @click="confirmDelete(item.id)"
              >
                <VIcon
                  icon="tabler-trash"
                  size="22"
                />
              </VBtn>
            </td>
          </tr>
          <tr v-if="courierStore.couriers.length === 0">
            <td
              colspan="4"
              class="text-center pa-4 text-disabled"
            >
              Belum ada akun kurir.
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <VDialog
      v-model="isDialogVisible"
      max-width="500"
    >
      <VCard :title="form.id ? 'Edit Akun Kurir' : 'Tambah Kurir Baru'">
        <VCardText>
          <VForm @submit.prevent="handleSubmit">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  label="Nama Lengkap"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.email"
                  label="Email"
                  type="email"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.password"
                  :label="form.id ? 'Password Baru (Opsional)' : 'Password'"
                  type="password"
                  variant="outlined"
                  :required="!form.id"
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
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    
    <VDialog
      v-model="isDeleteDialogVisible"
      max-width="400"
    >
      <VCard title="Hapus Akun?">
        <VCardText>Yakin ingin menghapus akun ini?</VCardText>
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
