<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { IconLink } from '@tabler/icons-vue';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { extractErrorMessage } from '@/lib/httpError';

const props = defineProps<{ testIdPrefix: string }>();

const emit = defineEmits<{
    (event: 'import-text', text: string): void;
}>();

const open = ref(false);
const http = useHttp<{ url: string }, { title: string; text: string }>({
    url: '',
});

const runImport = async (): Promise<void> => {
    if (!http.url.trim() || http.processing) {
        return;
    }

    try {
        const result = await http.post('/posts/firecrawl-import');
        emit('import-text', result.text);
        http.url = '';
        open.value = false;
    } catch (exception) {
        toast.error(
            extractErrorMessage(exception) ??
                trans('posts.composer.import_url.failed'),
        );
    }
};
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <button
                type="button"
                :data-testid="`${props.testIdPrefix}-import-url`"
                :aria-label="$t('posts.composer.import_url.title')"
                :title="$t('posts.composer.import_url.title')"
                class="flex size-8 items-center justify-center rounded-lg text-foreground transition-control hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring"
            >
                <IconLink class="size-4" />
            </button>
        </PopoverTrigger>
        <PopoverContent class="w-80 space-y-2 p-3" align="start">
            <p class="text-sm font-medium">
                {{ $t('posts.composer.import_url.title') }}
            </p>
            <Input
                v-model="http.url"
                type="url"
                inputmode="url"
                :placeholder="$t('posts.composer.import_url.placeholder')"
                :data-testid="`${props.testIdPrefix}-import-url-input`"
                @keydown.enter.prevent="runImport"
            />
            <Button
                type="button"
                size="sm"
                class="w-full"
                :disabled="http.processing || !http.url.trim()"
                :data-testid="`${props.testIdPrefix}-import-url-submit`"
                @click="runImport"
            >
                {{
                    http.processing
                        ? $t('posts.composer.import_url.importing')
                        : $t('posts.composer.import_url.import')
                }}
            </Button>
        </PopoverContent>
    </Popover>
</template>
