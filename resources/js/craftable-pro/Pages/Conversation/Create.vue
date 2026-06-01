<template>
    <PageHeader sticky :title="$t('craftable-pro', 'Create Conversation')">
        <Button
            :leftIcon="ArrowDownTrayIcon"
            @click="submit"
            :loading="form.processing"
            v-can="'craftable-pro.conversations.create'"
        >
            {{ $t("craftable-pro", "Save") }}
        </Button>
    </PageHeader>

    <Form :form="form" :submit="submit"  />
</template>

<script setup lang="ts">
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import { PageHeader, Button } from "craftable-pro/Components";
import { useForm } from "craftable-pro/hooks/useForm";
import Form from "./Form.vue";
import type { ConversationForm } from "./types";
import dayjs from 'dayjs'



interface Props {
    
}

const props = defineProps<Props>();

const { form, submit } = useForm<ConversationForm>(
    {
        created_by: "",
        name: "",
        type: ""
    },
    route("craftable-pro.conversations.store"),
    "post"
);
</script>
