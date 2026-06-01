<template>
    <PageHeader :title="$t('craftable-pro', 'Conversations')">
        <Button
            :leftIcon="PlusIcon"
            :as="Link"
            :href="route('craftable-pro.conversations.create')"
            v-can="'craftable-pro.conversations.create'"
        >
            {{ $t("craftable-pro", "New Conversation") }}
        </Button>
        <Button
            :leftIcon="ArrowDownTrayIcon"
            as="a"
            class="ml-2"
            @click="downloadFile"
        >
            {{ $t("craftable-pro", "Export") }}
        </Button>
    </PageHeader>

    <PageContent>
        <Listing
            :baseUrl="route('craftable-pro.conversations.index')"
            :data="conversations"
            dataKey="conversations"
        >
            <template #bulkActions="{ bulkAction }">
                <Modal type="danger">
                    <template #trigger="{ setIsOpen }">
                        <Button
                            @click="() => setIsOpen(true)"
                            color="gray"
                            variant="outline"
                            size="sm"
                            :leftIcon="TrashIcon"
                            v-can="'craftable-pro.conversations.destroy'"
                        >
                            {{ $t("craftable-pro", "Delete") }}
                        </Button>
                    </template>

                    <template #title>
                        {{ $t("craftable-pro", "Delete Conversation") }}
                    </template>

                    <template #content>
                        {{
                            $t(
                                "craftable-pro",
                                "Are you sure you want to delete selected Conversation? All data will be permanently removed from our servers forever. This action cannot be undone."
                            )
                        }}
                    </template>

                    <template #buttons="{ setIsOpen }">
                        <Button
                            @click.prevent="
                                () => {
                                    bulkAction('post', route('craftable-pro.conversations.bulk-destroy'), {
                                        onFinish: () => setIsOpen(false),
                                    });
                                }
                            "
                            color="danger"
                            v-can="'craftable-pro.conversations.destroy'"
                        >
                            {{ $t("craftable-pro", "Delete") }}
                        </Button>
                        <Button
                            @click.prevent="() => setIsOpen()"
                            color="gray"
                            variant="outline"
                        >
                            {{ $t("craftable-pro", "Cancel") }}
                        </Button>
                    </template>
                </Modal>
            </template>
            <template #tableHead>
                <ListingHeaderCell sortBy='id'>
                    {{ $t("craftable-pro", "Id") }}
                </ListingHeaderCell>
                <ListingHeaderCell sortBy='created_by'>
                    {{ $t("craftable-pro", "Created By") }}
                </ListingHeaderCell>
                <ListingHeaderCell sortBy='name'>
                    {{ $t("craftable-pro", "Name") }}
                </ListingHeaderCell>
                <ListingHeaderCell sortBy='type'>
                    {{ $t("craftable-pro", "Type") }}
                </ListingHeaderCell>
                <ListingHeaderCell sortBy='created_at'>
                    {{ $t("craftable-pro", "Created At") }}
                </ListingHeaderCell>
                <ListingHeaderCell>
                    <span class="sr-only">{{ $t("craftable-pro", "Actions") }}</span>
                </ListingHeaderCell>
            </template>
            <template #tableRow="{ item, action }: any">
                <ListingDataCell>
                     {{ item.id }}
                </ListingDataCell>
                <ListingDataCell>
                     {{ item.created_by }}
                </ListingDataCell>
                <ListingDataCell>
                     {{ item.name }}
                </ListingDataCell>
                <ListingDataCell>
                     {{ item.type }}
                </ListingDataCell>
                <ListingDataCell>
                     {{ item.created_at && dayjs(item.created_at).format('DD.MM.YYYY HH:mm') }}
                </ListingDataCell>
                <ListingDataCell>
                    <div class="flex items-center justify-end gap-3">
                        <IconButton
                            :as="Link"
                            :href="route('craftable-pro.conversations.edit', item)"
                            variant="ghost"
                            color="gray"
                            :icon="PencilSquareIcon"
                            v-can="'craftable-pro.conversations.edit'"
                        />

                        <Modal type="danger">
                            <template #trigger="{ setIsOpen }">
                                <IconButton
                                    @click="() => setIsOpen(true)"
                                    color="gray"
                                    variant="ghost"
                                    :icon="TrashIcon"
                                    v-can="'craftable-pro.conversations.destroy'"
                                />
                            </template>

                            <template #title>
                                {{ $t("craftable-pro", "Delete Conversation") }}
                            </template>

                            <template #content>
                                {{
                                    $t(
                                        "craftable-pro",
                                        "Are you sure you want to delete selected Conversation? All data will be permanently removed from our servers forever. This action cannot be undone."
                                    )
                                }}
                            </template>

                            <template #buttons="{ setIsOpen }">
                                <Button
                                    @click.prevent="
                                        () => {
                                            action('delete', route('craftable-pro.conversations.destroy', item), {
                                                onFinish: () => setIsOpen(false),
                                            });
                                        }
                                    "
                                    color="danger"
                                    v-can="'craftable-pro.conversations.destroy'"
                                >
                                    {{ $t("craftable-pro", "Delete") }}
                                </Button>
                                <Button
                                    @click.prevent="() => setIsOpen()"
                                    color="gray"
                                    variant="outline"
                                >
                                    {{ $t("craftable-pro", "Cancel") }}
                                </Button>
                            </template>
                        </Modal>
                    </div>
                </ListingDataCell>
            </template>
        </Listing>
    </PageContent>
</template>

<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import {
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    ArrowDownTrayIcon,
} from "@heroicons/vue/24/outline";
import {
    PageHeader,
    PageContent,
    Button,
    Listing,
    Avatar,
    ListingHeaderCell,
    ListingDataCell,
    Modal,
    Multiselect,
    IconButton,
    FiltersDropdown,
    Publish,
    ListingToggle,
} from "craftable-pro/Components";
import { PaginatedCollection } from "craftable-pro/types/pagination";
import type { Conversation } from "./types";
import type { PageProps } from "craftable-pro/types/page";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";

dayjs.extend(customParseFormat)



interface Props {
    conversations: PaginatedCollection<Conversation>;
}
defineProps<Props>();

const downloadFile = () => {
    const url = window.location.href.split("?");
    if(url.length > 1) {
        window.location = route('craftable-pro.conversations.export', url.pop()).slice(0, -1);
    } else {
        window.location = route('craftable-pro.conversations.export');
    }
}
</script>
