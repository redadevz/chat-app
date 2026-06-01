<template>
  <div>
    <FloatingContactWidget />
    <nav class="mt-5 space-y-1">
      <SidebarGroup :title="$t('craftable-pro', 'Content')">
        <SidebarItem
          :href="route('craftable-pro.media.index')"
          :icon="PhotoIcon"
          v-can="'craftable-pro.media.index'"
        >
          {{ $t("craftable-pro", "Media") }}
        </SidebarItem>
        <SidebarItem
          v-if="!isClient"
          :href="route('chats.index')"
          :icon="ChatBubbleLeftRightIcon"
        >
          {{ $t("craftable-pro", "Chat") }}
        </SidebarItem>
        <SidebarItem
            :href="route('craftable-pro.conversations.index')"
            :icon="CubeTransparentIcon"
            v-can="'craftable-pro.conversations.index'"
        >
            {{ $t("craftable-pro", "Conversations") }}
        </SidebarItem>
        <SidebarItem
            :href="route('craftable-pro.messages.index')"
            :icon="CubeTransparentIcon"
            v-can="'craftable-pro.messages.index'"
        >
            {{ $t("craftable-pro", "Messages") }}
        </SidebarItem>
        <!--AppendGeneratorLink-->
      </SidebarGroup>

      <SidebarGroup
        :title="$t('craftable-pro', 'System')"
        v-can:any="[
          'craftable-pro.craftable-pro-user.index',
          'craftable-pro.role.index',
          'craftable-pro.translation.index',
          'craftable-pro.settings.edit',
        ]"
      >
        <SidebarItem
          :href="route('craftable-pro.craftable-pro-users.index')"
          :icon="UsersIcon"
          v-can="'craftable-pro.craftable-pro-user.index'"
        >
          {{ $t("craftable-pro", "Access") }}
        </SidebarItem>
        <SidebarItem
          :href="route('craftable-pro.roles.index')"
          :icon="KeyIcon"
          v-can="'craftable-pro.role.index'"
        >
          {{ $t("craftable-pro", "Roles") }}
        </SidebarItem>
        <SidebarItem
          :href="route('craftable-pro.translations.index')"
          :icon="LanguageIcon"
          v-can="'craftable-pro.translation.index'"
        >
          {{ $t("craftable-pro", "Localization") }}
        </SidebarItem>
        <SidebarItem
          :href="route('craftable-pro.settings.index')"
          :icon="Cog8ToothIcon"
          v-can="'craftable-pro.settings.edit'"
        >
          {{ $t("craftable-pro", "Settings") }}
        </SidebarItem>
      </SidebarGroup>
    </nav>
  </div>
</template>

<script setup lang="ts">
import {
  KeyIcon,
  LanguageIcon,
  PhotoIcon,
  UsersIcon,
  Cog8ToothIcon,
  ChatBubbleLeftRightIcon,
  CubeTransparentIcon,
} from "@heroicons/vue/24/outline";
import { SidebarItem, SidebarGroup } from "craftable-pro/Components";
import FloatingContactWidget from "@/craftable-pro/Components/FloatingContactWidget.vue";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const isClient = computed(() => (page.props.auth?.roles ?? []).includes("client"));
</script>
