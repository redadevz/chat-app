<template>
    <PageHeader
        sticky
        :title="$t('craftable-pro', 'Edit Conversation')"
        :subtitle="`Last updated at ${dayjs(
            conversation.updated_at
        ).format('DD.MM.YYYY')}`"
    >
        <Button
            :leftIcon="ArrowDownTrayIcon"
            @click="submit"
            :loading="form.processing"
            v-can="'craftable-pro.conversations.edit'"
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
import type { Conversation, ConversationForm } from "./types";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";


dayjs.extend(customParseFormat);



interface Props {
    conversation: Conversation;
    
}

const props = defineProps<Props>();

const { form, submit } = useForm<ConversationForm>(
    {
        created_by: props.conversation?.created_by ?? "",
        name: props.conversation?.name ?? "",
        type: props.conversation?.type ?? ""
    },
    route("craftable-pro.conversations.update", [props.conversation?.id])
);
</script>
