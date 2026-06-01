<template>
    <PageHeader sticky :title="$t('craftable-pro', 'Create Message')">
        <Button
            :leftIcon="ArrowDownTrayIcon"
            @click="submit"
            :loading="form.processing"
            v-can="'craftable-pro.messages.create'"
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
import type { MessageForm } from "./types";
import dayjs from 'dayjs'



interface Props {
    
}

const props = defineProps<Props>();

const { form, submit } = useForm<MessageForm>(
    {
        conversation_id: "",
        user_id: "",
        reply_to_id: "",
        body: "",
        type: ""
    },
    route("craftable-pro.messages.store"),
    "post"
);
</script>
