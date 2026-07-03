<script setup lang="ts">
import type { Component } from "vue";

interface NavItem {
  title: string;
  route: string;
  icon: Component;
}

// Bottom navigation for authenticated areas. Icons are lucide-vue component
// references (not name strings) because nuxt-lucide-icons registers each icon
// as its own auto-imported component rather than one generic <Icon> component.
const navItems: NavItem[] = [];
</script>

<template>
  <div class="h-screen pt-20 mx-4">
    <slot />
  </div>
  <footer class="fixed bottom-0 w-full bg-gray-100 py-4">
    <div class="flex justify-around py-2">
      <NuxtLink
        v-for="(item, index) in navItems"
        :key="index"
        :to="item.route"
        class="flex flex-col items-center text-gray-600 hover:text-cyan-500"
      >
        <component :is="item.icon" class="text-xl" />
        <span>{{ item.title }}</span>
      </NuxtLink>
    </div>
  </footer>
</template>
