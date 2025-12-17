<template>
  <div class="h-100 d-flex align-center justify-space-between text-medium-emphasis">
    <span class="d-flex align-center">
      &copy;
      {{ new Date().getFullYear() }}
      Made with
      <VIcon
        icon="tabler-heart"
        color="error"
        size="1.25rem"
        class="mx-1"
      />
      By <a
        href="#"
        target="_blank"
        rel="noopener noreferrer"
        class="text-primary ms-1 font-weight-bold"
      >Kebab Mantap Team</a>
    </span>
  </div>
</template>
