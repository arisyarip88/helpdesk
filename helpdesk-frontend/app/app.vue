<template>
  <div>
    <NuxtLayout>
      <!-- Top Progress Bar Transisi Halaman -->
    <NuxtLoadingIndicator 
      :height="3" 
      color="linear-gradient(to right, #4f46e5, #9333ea, #06b6d4)" 
      :duration="2000"
      :throttle="100"
    />
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>