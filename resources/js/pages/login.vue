<script setup>
import { useAuthStore } from '@/stores/auth'
import { ref } from 'vue'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const form = ref({
  email: '',
  password: '',
})

const isPasswordVisible = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const authStore = useAuthStore()

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''

  const result = await authStore.login(form.value)

  if (!result.success) {
    errorMessage.value = result.message
  }
  
  isLoading.value = false
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <VCard
      class="auth-card pa-4 pt-7"
      max-width="448"
      width="100%"
    >
      <VCardItem class="justify-center">
        <template #prepend>
          <div class="d-flex">
            <VIcon
              icon="tabler-brand-shopee"
              color="primary"
              size="32"
            />
          </div>
        </template>
        <VCardTitle class="font-weight-bold text-h5 text-primary">
          E-Commerce Admin
        </VCardTitle>
      </VCardItem>

      <VCardText class="pt-2 text-center">
        <h5 class="text-h5 mb-1">
          Selamat Datang! 👋🏻
        </h5>
        <p class="mb-0">
          Silakan masuk ke akun Anda
        </p>
      </VCardText>

      <VCardText>
        <VForm @submit.prevent="handleLogin">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="Email"
                placeholder="admin@ecommerce.com"
                type="email"
                prepend-inner-icon="tabler-mail"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="Kata Sandi"
                :type="isPasswordVisible ? 'text' : 'password'"
                prepend-inner-icon="tabler-lock"
                :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                variant="outlined"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
            </VCol>

            <VCol
              v-if="errorMessage"
              cols="12"
            >
              <VAlert
                type="error"
                density="compact"
                variant="tonal"
              >
                {{ errorMessage }}
              </VAlert>
            </VCol>

            <VCol cols="12">
              <VBtn
                block
                type="submit"
                color="primary"
                size="large"
                :loading="isLoading"
              >
                Masuk
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </div>
</template>

<style lang="scss">
  @use "@core-scss/template/pages/page-auth";
</style>
