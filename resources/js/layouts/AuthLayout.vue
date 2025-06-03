<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<style>
   .parallelogram {
      background-color: var(--foreground);
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
</style>

<template>
   <Head title="Login">
      <link rel="preconnect" href="https://rsms.me/" />
      <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
   </Head>
   <div class="w-full h-screen bg-netral">
      <div class="flex w-screen relative overflow-hidden bg-netral">
         <div class="hidden opacity-70 z-20 md:block absolute w-[60%] h-screen bg-netral-[80%] translate-x-[-60%] left-[20%]">
            <div class="relative parallelogram w-[70%] z-20 skew-x-[-20deg] h-screen">
               <div class="image animate-fade-left animate-once animate-ease-in" :style="{ backgroundImage: `url(/assets/images/background.jpg)` }"></div>
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
            <div class="hidden 2xl:block animate-fade animate-ease-linear absolute p-20 w-1/2 h-[50vh] rounded-lg -translate-x-1/2 -translate-y-1/2 left-1/2 top-1/2">
               <div class="animate-fade-right animate-ease-in text-5xl w-[500px] absolute p-2.5 px-10 rounded-[30px] font-semibold left-[10%] -translate-y-1/2 top-1/2">
                  <blockquote class="bl-lg text-5xl text-primary text-shadow">
                     Stay Healthy, Stay Happy!
                  </blockquote>
                  <br>
                  <blockquote class="text-2xl text-primary/80 text-shadow">
                     Health is not valued until sickness comes.
                  </blockquote>
               </div>
            </div>
            <div class="animate-fade-down">
               <div class="p-2 sm:p-0 w-full h-screen sm:h-auto xl:w-1/2 2xl:w-[30%] xl:-translate-x-1/2 xl:-translate-y-1/2 top-0 xl:top-[50vh] xl:left-[60vw] 2xl:left-[70vw] relative rounded-lg">
                  <slot />
               </div>
            </div>
         </div>
         
      </div>
   </div>
</template>