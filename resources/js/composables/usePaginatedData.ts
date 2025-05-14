// composables/usePaginatedData.ts
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';

interface FetchOptions {
    routeName: string;
    queryParams?: Record<string, any>;
}

export function usePaginatedData() {
    const page = ref(1);
    const perPage = ref(15);
    const search = ref('');
    const loading = ref(false);
    const data = ref();

    const fetchData = async ({ routeName, queryParams = {} }: FetchOptions) => {
        loading.value = true;
        await fetch(route(routeName, { page: page.value, per_page: perPage.value, search: search.value, ...queryParams }))
            .then((res) => res.json())
            .then((res) => (data.value = res.data))
            .finally(() => (loading.value = false));
    };

    const changePage = (event: any) => {
        page.value = event.page + 1;
    };

    const handlePerPage = (value: any) => {
        perPage.value = value;
    };

    watchDebounced(search, () => fetchData, { debounce: 1000 });

    return {
        page,
        perPage,
        search,
        loading,
        data,
        fetchData,
        changePage,
        handlePerPage,
    };
}
