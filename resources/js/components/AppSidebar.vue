<script setup lang="ts">
import { ref, onMounted } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { menu } from '@/services/menu';

const mainNavItems = ref<NavItem[]>([]); // awalnya kosong

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

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

// Utility: resolve string icon name ke icon component
function resolveIcon(name: string) {
    const icons = { LayoutGrid, Folder, BookOpen };
    return icons[name as keyof typeof icons] || LayoutGrid;
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
