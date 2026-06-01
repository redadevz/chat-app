<template>
    <PageContent>
        <div class="mx-auto max-w-3xl 2xl:max-w-4xl">
            

            <div class="flex flex-col gap-4 md:flex-row">
                <div class="flex-1 space-y-4">
                    <Card>
                        <div class="space-y-4">
                            <TextInput
                                v-model="form.conversation_id"
                                name="conversation_id"
                                :label="$t('craftable-pro', 'Conversation Id')"
                                type="text"
                            />
                            <TextInput
                                v-model="form.user_id"
                                name="user_id"
                                :label="$t('craftable-pro', 'User Id')"
                                type="text"
                            />
                            <TextInput
                                v-model="form.reply_to_id"
                                name="reply_to_id"
                                :label="$t('craftable-pro', 'Reply To Id')"
                                type="text"
                            />
                            <Wysiwyg
                                v-model="form.body"
                                name="body"
                                :label="$t('craftable-pro', 'Body')"
                            />
                            <TextInput
                                v-model="form.type"
                                name="type"
                                :label="$t('craftable-pro', 'Type')"
                                type="text"
                            />
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue"
import {
    Card,
    TextInput,
    Wysiwyg,
    TextArea,
    PageContent,
    DatePicker,
    Checkbox,
    Dropzone,
    ImageUpload,
    Multiselect,
    CardLocaleSwitcher
} from "craftable-pro/Components";
import { InertiaForm } from "craftable-pro/types";
import type { MessageForm } from "./types";



interface Props {
    form: InertiaForm<MessageForm>;
    submit: void;
    
}

const props = defineProps<Props>();

</script>
