<script setup lang="ts">
import { createApp, App, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import TabulatorFilter from './TabulatorFilter.vue';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import { Select } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface Props {
  id ?: string | null;
  data: any[];
  columns: any[];
  usingFilter: boolean;
  options ?: Record<string, any> | null;
}

interface RowData {
  id: number;
  name: string;
  [key: string]: any;
}

interface HTMLElementWithApp extends HTMLElement {
  _vueApp?: App<Element>;
}

const props = withDefaults(defineProps<Props>(), {
  usingFilter: false,
});

const table = ref(null);
let tabulator: any = null; // tipe any dulu, karena import dinamis

const mergedOptions = {
  renderVerticalBuffer:300,
  layout: 'fitDataStretch',
  autoColumns:true,
  resizableRows: false,
  resizableRowGuide: true,
  resizableColumnGuide: true,
  rowHeader:{
    formatter:"rownum", 
    headerSort:false, 
    hozAlign:"right", 
    resizable:false, 
    frozen:true
  },
  // autoColumnsDefinitions:[
  //   {field:"name", editor:"input"}, //add input editor to the name column
  //   {field:"age", headerFilter:true}, //add header filters to the age column
  // ],
  columnDefaults: {
    resizable: true,
  },
  reactiveData: true,
  selectable: true,
  pagination: false,
  width: '100%',
  data: props.data,
  columns: props.columns,
  rowFormatter: function(row){
      let cell = row.getCell("actions");
      if(!cell) return;

      const el = cell.getElement();

      // Cek kalau belum ada Vue mounted
      if(!el._vueApp){
          // Mount Vue component ke cell element
          const app = Vue.createApp(MyActionButtons, { rowData: row.getData() });
          app.mount(el);
          el._vueApp = app;
      }
  },

  ...props.options,
};

onMounted(async () => {
  if (!table.value) {
    return;
  }

  tabulator = new Tabulator(table.value, mergedOptions);
  console.log('Tabulator instance created:', tabulator);
});

watch(
  () => props.data,
  (newData) => {
    if (tabulator) {
      tabulator.replaceData(newData);
    }
  },
  { deep: true }
);

onBeforeUnmount(() => {
  if (tabulator) {
    tabulator.destroy();
    tabulator = null;
  }
});
</script>

<template>
  <div class="w-full gap-2 grid">
    <!-- Toolbar Slot Container -->
    <div v-if="$slots.toolbar" class="w-full p-3 rounded-sm bg-primary/40">
      <slot name="toolbar" />
    </div>

    <!-- Toolbar Slot Default Container -->
    <div v-else-if="usingFilter || $slots.defaultToolbar">
      <div
        class="w-full text-sm p-3 flex flex-wrap gap-2 rounded-sm bg-primary/40"
      >
        <TabulatorFilter class="w-max" label="Field">
          <Select class="my-auto" id="filter-field">
            <option></option>
            <template v-for="column in columns" :key="column.field || column.title">
              <optgroup
                v-if="'columns' in column"
                :label="column.title"
                :key="`group-${column.title}`"
              >
                <option
                  v-for="sub_column in column.columns"
                  :key="sub_column.field"
                  :value="sub_column.field"
                >
                  {{ sub_column.title }}
                </option>
              </optgroup>
              <option
                v-else
                :value="column.field"
              >
                {{ column.title }}
              </option>
            </template>
          </Select>
        </TabulatorFilter>
        <TabulatorFilter class="w-max" label="Operator">
          <Select class="my-auto" id="filter-operator">
            <option value="=">=</option>
            <option value="<"><</option>
            <option value="<="><=</option>
            <option value=">">></option>
            <option value=">=">>=</option>
            <option value="!=">!=</option>
            <option value="like">like</option>
          </Select>
        </TabulatorFilter>
        <TabulatorFilter class="w-max" label="Value">
          <Input
            class="my-auto"
            type="text"
            placeholder="value of filter"
            id="filter-value"
          />
        </TabulatorFilter>
        <slot name="defaultToolbar" />
      </div>
    </div>

    <!-- Table -->
    <div
      ref="table"
      :id="id || ''"
      class="tabulator-container w-full !mt-[2px] 2xl:h-[calc(100vh-160px)] xl:h-[calc(100vh-140px)] h-[calc(100vh-120px)]"
    ></div>
  </div>
</template>
