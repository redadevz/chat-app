<template>
  <PageHeader sticky :title="$t('craftable-pro', 'Settings')">
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
    <template #tabs>
      <TabGroup variant="underline">
        <Tab>
          {{ $t("craftable-pro", "General") }}
        </Tab>
        <Tab disabled>
          <div class="flex items-center gap-3">
            {{ $t("craftable-pro", "Security") }}
            <Tag color="amber" size="sm">{{
                $t("craftable-pro", "Coming soon...")
              }}</Tag>
          </div>
        </Tab>
        <Tab>
          {{ $t("craftable-pro", "Chat permissions") }}
        </Tab>
      </TabGroup>
    </template>

    <TabPanel>
      <div class="divide-y divide-slate-200 dark:divide-slate-800 [&>*]:py-5 max-w-screen-lg mx-auto">
        <Multiselect
          v-model="form.available_locales"
          mode="tags"
          name="available_locales"
          :required="true"
          :label="$t('craftable-pro', 'Available locales')"
          :options="availableLocales"
          :placeholder="$t('craftable-pro', 'Type abbreviation and hit enter')"
          :createOption="true"
          labelPlacement="left"
          :leftIcon="MagnifyingGlassIcon"
        >
          <template #tag="{ option, handleTagRemove, disabled }">
            <Tag
              variant="outline"
              @dismiss="(event) => handleTagRemove(option, event)"
              :dissmisable="true"
            >
              <LocaleFlag :locale="option.label" />
            </Tag>
          </template>
          <template #option="{ option, search }">
            <LocaleFlag :locale="option.label" />
          </template>
        </Multiselect>
        <Multiselect
          v-model="form.default_locale"
          mode="single"
          name="default_locale"
          :label="$t('craftable-pro', 'Default locale')"
          :options="form.available_locales"
          labelPlacement="left"
          inputClass="w-1/2"
        >
          <template #singlelabel="{ value }">
            <LocaleFlag :locale="value.label" />
          </template>
          <template #option="{ option, search }">
            <LocaleFlag :locale="option.label" />
          </template>
        </Multiselect>
        <Multiselect
          v-model="form.default_route"
          mode="single"
          name="default_route"
          :label="$t('craftable-pro', 'Default route')"
          :options="availableRoutes"
          labelPlacement="left"
        />
      </div>
    </TabPanel>

    <TabPanel>
    </TabPanel>

    <!-- Chat permissions: a role × target-role matrix. Ticking a cell lets the
         row role start a chat with the column role. "everyone" overrides all. -->
    <TabPanel>
      <div class="max-w-screen-lg mx-auto py-5">
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
          {{ $t("craftable-pro", "Choose which roles each role is allowed to start a new chat with.") }}
        </p>

        <div v-if="chatLoading" class="py-8 text-center text-sm text-slate-400">
          {{ $t("craftable-pro", "Loading…") }}
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-200 dark:border-slate-700">
                <th class="py-2 pr-4 text-left font-semibold text-slate-600 dark:text-slate-300">
                  {{ $t("craftable-pro", "Role") }}
                </th>
                <th
                  v-for="col in chatColumns"
                  :key="col.permission"
                  class="px-3 py-2 text-center font-medium capitalize text-slate-600 dark:text-slate-300"
                >
                  {{ col.label }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="role in chatRoles"
                :key="role.id"
                class="border-b border-slate-100 dark:border-slate-800"
              >
                <td class="py-2 pr-4 font-medium text-slate-700 dark:text-slate-200">
                  {{ role.name }}
                </td>
                <td
                  v-for="col in chatColumns"
                  :key="col.permission"
                  class="px-3 py-2 text-center"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    :checked="role.perms.includes(col.permission)"
                    @change="togglePerm(role, col.permission)"
                  />
                </td>
              </tr>
            </tbody>
          </table>

          <div class="mt-6 flex justify-end">
            <Button :loading="chatSaving" @click="saveChatPermissions">
              {{ $t("craftable-pro", "Save chat permissions") }}
            </Button>
          </div>
        </div>
      </div>
    </TabPanel>
  </PageContent>
</template>



<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import {ArrowDownTrayIcon} from "@heroicons/vue/24/outline";
import {
  PageHeader,
  PageContent,
  Button,
  Multiselect,
  Tag,
  Tab,
  TabGroup,
  LocaleFlag,
} from "craftable-pro/Components";
import { useForm } from "craftable-pro/hooks/useForm";
import { GeneralSettings } from "craftable-pro/Pages/Settings/types";
import {TabPanel} from "@headlessui/vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/solid";

interface Props {
  generalSettings: GeneralSettings;
  availableRoutes: string[];
}

const props = defineProps<Props>();

const availableLocales = props.generalSettings.available_locales;

const { form, submit } = useForm<GeneralSettings>(
  {
    available_locales: props.generalSettings.available_locales,
    default_locale: props.generalSettings.default_locale,
    default_route: props.generalSettings.default_route,
  },
  route("craftable-pro.settings.update")
);

// --- Chat permissions matrix -------------------------------------------------
interface ChatColumn {
  permission: string;
  label: string;
}
interface ChatRole {
  id: number;
  name: string;
  perms: string[];
}

const chatColumns = ref<ChatColumn[]>([]);
const chatRoles = ref<ChatRole[]>([]);
const chatLoading = ref(true);
const chatSaving = ref(false);

async function loadChatPermissions() {
  chatLoading.value = true;
  try {
    const { data } = await window.axios.get(route("chats.permissions.index"));
    chatColumns.value = data.columns;
    chatRoles.value = data.roles;
  } finally {
    chatLoading.value = false;
  }
}

function togglePerm(role: ChatRole, permission: string) {
  const idx = role.perms.indexOf(permission);
  if (idx === -1) {
    role.perms.push(permission);
  } else {
    role.perms.splice(idx, 1);
  }
}

function saveChatPermissions() {
  chatSaving.value = true;
  router.put(
    route("chats.permissions.update"),
    { roles: chatRoles.value.map((r) => ({ id: r.id, perms: r.perms })) },
    {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => {
        chatSaving.value = false;
      },
    }
  );
}

onMounted(loadChatPermissions);
</script>
