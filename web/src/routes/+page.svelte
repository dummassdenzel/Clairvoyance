<script lang="ts">
  import { authStore } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import Login from "./login/+page.svelte";


  
	$: if (!$authStore.isAuthenticated) {
    if ($authStore.user && $authStore.user.role !== 'admin') {
      goto('/user/dashboard');
    } else if ($authStore.user && $authStore.user.role === 'admin') {
      goto('/admin/dashboard');
    } else {
      goto('/login');
    }
  }

</script>

<Login />