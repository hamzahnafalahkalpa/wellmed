import { computed, ref } from 'vue';

export function useDialog() {
    const dialogType = ref<'edit' | 'delete' | null>(null);
    const selected = ref<any>(null);

    const openDialog = (type: 'edit' | 'delete', value: any = null) => {
        dialogType.value = type;
        selected.value = value;
    };

    const closeDialog = () => {
        dialogType.value = null;
        selected.value = null;
    };

    const isEditDialogOpen = computed(() => dialogType.value === 'edit');
    const isDeleteDialogOpen = computed(() => dialogType.value === 'delete');

    return {
        dialogType,
        selected,
        openDialog,
        closeDialog,
        isEditDialogOpen,
        isDeleteDialogOpen,
    };
}
