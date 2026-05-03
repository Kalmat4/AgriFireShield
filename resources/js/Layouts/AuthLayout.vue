<template>
    <div class="auth-bg">
        <div class="auth-box">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.auth-bg {
    min-height: 100vh;
    background: #060d08;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.auth-box {
    width: 100%;
    max-width: 420px;
    background: #1a1a1a;
    border: 1px solid #2d2d2d;
    border-radius: 16px;
    padding: 40px 36px;
}
</style>
