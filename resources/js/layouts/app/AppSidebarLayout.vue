<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';
import { menu } from '@projects/klinik/src/Resources/js/services/menu';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

onMounted(async () => {
    try {
        const response = await menu();
        console.log(response);
        // Transform jika perlu, misal API return format seperti:
        // [{ title: 'Dashboard', href: '/dashboard', icon: 'LayoutGrid' }]
        // mainNavItems.value = data.map((item: any) => ({
        //     ...item,
        //     icon: resolveIcon(item.icon), // convert dari string ke icon component
        // }));
    } catch (err) {
        console.error('Failed to load menu:', err);
    }
});

</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
