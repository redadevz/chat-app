<template>
    <PageHeader
        sticky
        :title="$t('craftable-pro', 'Edit Message')"
        :subtitle="`Last updated at ${dayjs(
            message.updated_at
        ).format('DD.MM.YYYY')}`"
    >
        <Button
            :leftIcon="ArrowDownTrayIcon"
            @click="submit"
            :loading="form.processing"
            v-can="'craftable-pro.messages.edit'"
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
import type { Message, MessageForm } from "./types";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";


dayjs.extend(customParseFormat);



interface Props {
    message: Message;
    
}

const props = defineProps<Props>();

const { form, submit } = useForm<MessageForm>(
    {
        conversation_id: props.message?.conversation_id ?? "",
        user_id: props.message?.user_id ?? "",
        reply_to_id: props.message?.reply_to_id ?? "",
        body: props.message?.body ?? "",
        type: props.message?.type ?? ""
    },
    route("craftable-pro.messages.update", [props.message?.id])
);
</script>
