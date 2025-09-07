import type { DefineComponent } from 'vue';

export const globMap: Record<string, Record<string, () => Promise<DefineComponent>>> = {
  "localhost:8004": {
    ...import.meta.glob<DefineComponent>('@tenants/tenant-klinik/src/Resources/js/pages/**/*.vue'),
    ...import.meta.glob<DefineComponent>('@groups/group-initial-klinik/src/Resources/js/pages/**/*.vue'),
    ...import.meta.glob<DefineComponent>('@projects/klinik/src/Resources/js/pages/**/*.vue'),
  }
};


