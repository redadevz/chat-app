<template>
  <PageHeader
    sticky
    :title="$t('craftable-pro', 'Chat appearance')"
  >
    <Button
      :leftIcon="ArrowDownTrayIcon"
      @click="submit"
      :loading="form.processing"
      v-can="'craftable-pro.settings.edit'"
    >
      {{ $t("craftable-pro", "Save") }}
    </Button>
  </PageHeader>

  <PageContent>
    <div class="mx-auto grid max-w-screen-lg gap-6 md:grid-cols-2">
      <Card>
        <div class="space-y-6">
          <div>
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
              {{ $t('craftable-pro', 'Colors') }}
            </h3>
            <p class="mt-1 text-xs text-slate-500">
              {{ $t('craftable-pro', 'Used across the chat for public messages and internal/private notes.') }}
            </p>
          </div>

          <div class="space-y-1">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">
              {{ $t('craftable-pro', 'Public / message color') }}
            </label>
            <div class="flex items-center gap-3">
              <input
                type="color"
                v-model="form.public_color"
                class="h-9 w-12 cursor-pointer rounded border border-slate-300 bg-transparent p-0.5 dark:border-slate-700"
              />
              <TextInput
                v-model="form.public_color"
                name="public_color"
                type="text"
                class="flex-1"
              />
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">
              {{ $t('craftable-pro', 'Internal / private color') }}
            </label>
            <div class="flex items-center gap-3">
              <input
                type="color"
                v-model="form.internal_color"
                class="h-9 w-12 cursor-pointer rounded border border-slate-300 bg-transparent p-0.5 dark:border-slate-700"
              />
              <TextInput
                v-model="form.internal_color"
                name="internal_color"
                type="text"
                class="flex-1"
              />
            </div>
          </div>
        </div>
      </Card>

      <Card>
        <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
          {{ $t('craftable-pro', 'Preview') }}
        </h3>
        <div class="flex flex-col gap-2 rounded-lg bg-[#1e1f22] p-4">
          <div class="flex justify-start">
            <div class="max-w-[75%] rounded-2xl bg-white/10 px-3 py-2 text-sm text-gray-100">
              {{ $t('craftable-pro', 'Hi, I need some help.') }}
            </div>
          </div>
          <div class="flex justify-end">
            <div
              class="max-w-[75%] rounded-2xl px-3 py-2 text-sm text-white"
              :style="{ backgroundColor: form.public_color }"
            >
              {{ $t('craftable-pro', 'Sure, happy to help!') }}
            </div>
          </div>
          <div class="flex justify-end">
            <div
              class="max-w-[75%] rounded-2xl px-3 py-2 text-sm"
              :style="internalPreviewStyle"
            >
              <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide" :style="{ color: form.internal_color }">
                {{ $t('craftable-pro', 'Internal · staff only') }}
              </p>
              {{ $t('craftable-pro', 'FYI — handled by the account manager.') }}
            </div>
          </div>
        </div>
      </Card>
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import {
  PageHeader,
  PageContent,
  Card,
  Button,
  TextInput,
} from "craftable-pro/Components";
import { useForm } from "craftable-pro/hooks/useForm";

interface Props {
  chatAppearance: {
    public_color: string;
    internal_color: string;
  };
}

const props = defineProps<Props>();

const { form, submit } = useForm(
  {
    public_color: props.chatAppearance.public_color,
    internal_color: props.chatAppearance.internal_color,
  },
  route("craftable-pro.chat-appearance.update")
);

const internalPreviewStyle = computed(() => ({
  backgroundColor: `color-mix(in srgb, ${form.internal_color} 15%, transparent)`,
  color: `color-mix(in srgb, ${form.internal_color} 35%, white)`,
  boxShadow: `inset 0 0 0 1px color-mix(in srgb, ${form.internal_color} 40%, transparent)`,
}));
</script>
