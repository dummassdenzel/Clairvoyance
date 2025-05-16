<script lang="ts">
    import { onMount } from "svelte";
    
    let username = "";
    let email = "";
    let password = "";
    let confirmPassword = "";
  
    $: isFormValid = username && email && password && password === confirmPassword;
  </script>
  
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white p-8 ">
      <div class="flex flex-col items-center mb-6">
        <!-- Logo and Title -->
        <img src="logo.png" alt="Clairvoyance Logo" class="w-80 h-11" />
        <p class="text-gray-500 text-sm mt-1">Please, log in to access your account.</p>
      </div>
  
      <form class="space-y-4">  
        <div class="relative">
          <i 
            class="ph ph-envelope-simple text-lg absolute left-3 top-1/2 transform -translate-y-1/2"
            class:text-blue-950={email.length > 0}
            class:text-gray-300={email.length === 0}
          ></i>
          <input
          type="email"
          placeholder="email"
          bind:value={email}
          class="w-full px-4 py-2 pl-10 text-blue-950 placeholder-gray-300 border border-gray-300 rounded-md outline-gray-300"
          />
        </div>
        
        <div class="relative">
          <i 
            class="ph ph-lock-simple text-lg absolute left-3 top-1/2 transform -translate-y-1/2"
            class:text-blue-950={password.length > 0}
            class:text-gray-300={password.length === 0}
          ></i>
          <input
          type="password"
          placeholder="password"
          bind:value={password}
          class="w-full px-4 py-2 pl-10 text-blue-950 placeholder-gray-300 border border-gray-300 rounded-md outline-gray-300"
          />
        </div>
        
        <button
          type="submit"
          class="w-full py-2 rounded-2xl bg-gray-300  hover:bg-blue-950 text-gray-700 hover:text-white font-semibold transition"
          disabled={!isFormValid}
        >
          Continue →
        </button>
      </form>

      <hr class="border-t-[1px] border-gray-300 my-2 mx-4 mt-6" />
  
      <p class="mt-2 text-center text-sm text-gray-500">
        don't have an account?
        <a href="/register" class="text-blue-950 font-bold hover:underline">register.</a>
      </p>
    </div>
  </div>
  