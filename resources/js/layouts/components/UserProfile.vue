<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const logout = () => {
  authStore.logout()
}
</script>

<template>
  <VBadge
    dot
    location="bottom right"
    offset-x="3"
    offset-y="3"
    color="success"
    bordered
  >
    <VAvatar
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <span class="text-h6 font-weight-semibold">{{ authStore.userName.charAt(0).toUpperCase() }}</span>
      
      <VMenu
        activator="parent"
        width="230"
        location="bottom end"
        offset="14px"
      >
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                >
                  <VAvatar
                    color="primary"
                    variant="tonal"
                  >
                    <span>{{ authStore.userName.charAt(0).toUpperCase() }}</span>
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold">
              {{ authStore.userName }}
            </VListItemTitle>
            <VListItemSubtitle class="text-capitalize">
              {{ authStore.userRole }}
            </VListItemSubtitle>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem link>
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-user"
                size="22"
              />
            </template>
            <VListItemTitle>Profil Saya</VListItemTitle>
          </VListItem>

          <VListItem @click="logout">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-logout"
                size="22"
              />
            </template>
            <VListItemTitle>Keluar</VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
