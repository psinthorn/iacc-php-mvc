<?php
$message = $_SESSION['flash_success'] ?? '';
?>

<div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3 animate-slide-in"
     x-data="{ show: true }" x-show="show"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <i class="fas fa-check-circle text-green-600 text-xl mt-0.5 flex-shrink-0"></i>
    
    <div class="flex-1">
        <p class="font-semibold text-green-800"><?php echo htmlspecialchars($message); ?></p>
    </div>
    
    <button @click="show = false" class="text-green-600 hover:text-green-800 flex-shrink-0 mt-0.5">
        <i class="fas fa-times"></i>
    </button>
</div>
