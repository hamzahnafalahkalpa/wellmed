<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import background from './../../images/background.jpg';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<!-- <template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
    
</template> -->

<!-- <script setup>
   import InputLabel from '@/Components/InputLabel.vue';
   import PrimaryButton from '@/Components/PrimaryButton.vue';
   import TextInput from '@/Components/TextInput.vue';
   import InputError from '@/Components/InputError.vue';
   import Checkbox from '@/Components/Checkbox.vue';
   import { Link } from '@inertiajs/vue3';
</script>

<style>
   .parallelogram {
      background-color: #ccc;
      overflow: hidden;
   }

   .parallelogram .image {
      transform: skew(20deg);
      width: 80vw;
      height: 100vh;
      background-size: cover;
      filter: grayscale(100%);
      position: relative;
      left: -30%
   }

   .login-container::before{
      content : "";
      position: fixed;
      top: 0px;
      left: 0px;
      background-color: rgba(0,0,0,.2);
      width: 100vw;
      height: 100vh;
      z-index: 40;
   }
</style>-->

<template>
   <div class="w-full h-screen bg-netral">
      <div class="flex w-screen relative overflow-hidden bg-netral">
         <div class="hidden opacity-70 z-20 md:block absolute w-[60%] h-screen bg-netral-[80%] translate-x-[-60%] left-[20%]">
            <div class="relative parallelogram w-[70%] z-20 skew-x-[-20deg] h-screen">
               <div class="image animate-fade-left animate-once animate-ease-in" :style="{ backgroundImage: `url(${background})` }"></div>
            </div>
            <div class="absolute parallelogram w-[70%] z-10 skew-x-[20deg] h-screen top-[0px]">
               <div class="animate-fade-right animate-once animate-ease-in"></div>
            </div>
            <div class="absolute parallelogram bg-primary w-[70%] z-10 left-[180%] skew-x-[-20deg] h-screen top-[0px]">
               <div class="animate-fade-right animate-once animate-ease-in"></div>
            </div>
            <div class="absolute parallelogram w-[70%] z-10 left-[180%] skew-x-[20deg] h-screen top-[0px]">
               <div class="animate-fade-right animate-once animate-ease-in"></div>
            </div>
         </div>
         <div class="min-h-screen relative w-full z-40">
            <div class="hidden 2xl:block animate-fade animate-ease-linear absolute p-20 w-1/2 h-[50vh] rounded-lg translate-x-[-50%] translate-y-[-50%] left-[50%] top-[40%]">
               <h1 class="animate-fade-right animate-ease-in text-5xl absolute p-2.5 px-10 rounded-[30px] text-white font-semibold left-[-10%]">
                  WELCOME!
               </h1>
               <div class="animate-fade-right animate-ease-in text-5xl w-[500px] absolute p-2.5 px-10 rounded-[30px] font-semibold left-[10%] top-[200px]">
                  <blockquote class="bl-lg text-5xl text-primary text-shadow">
                     Put your creativity inside
                  </blockquote>
                  <br>
                  <blockquote class="text-2xl text-secondary text-shadow">
                     Design is not just about how it looks, but how it works and feels
                  </blockquote>
               </div>
            </div>
            <div class="animate-fade-down">
               <div class="w-full h-screen sm:h-auto xl:w-[50%] 2xl:w-[30%] xl:translate-x-[-50%] xl:-translate-y-1/2 top-0 xl:top-[50vh] xl:left-[60vw] 2xl:left-[70vw] relative rounded-lg">
                  <slot name="form"></slot>            
               </div>
            </div>
         </div>
         
      </div>
   </div>
</template> -->