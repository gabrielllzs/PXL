<script setup>
import { ref } from 'vue'

const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.content || '')
</script>

<template>
    <div class="container">
        <div id="login-box">
            <div id="user-info">
                <img src="../../../images/user-image.png" alt="orange kitty">
                <h1>Administrator</h1>
            </div>
            <div id="login">
                <div id="login-form">
                    <form action="/login" method="POST">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <div class="user-box">
                            <label>Email</label>
                            <input type="text" name="email" required="">
                        </div>
                        <div class="user-box">
                            <label>Password</label>
                            <input type="password" name="password" required="">
                        </div>
                        <div id="login-button">
                            <button type="submit">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    width: 100vw;
    background-image: url('../../../images/login-bg.png');
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
    color: white;
}

#login-box{
    display: flex;
    flex-direction: column;
    align-items: center;
    border-radius: 10px;
    width: 300px;
    padding: 20px;
}

#user-info{
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 20px;
}

#user-info img{
    border: 1px solid white;
    width: 100px;
    border-radius: 4px;
}

#user-info h1{
    margin-top: 10px;
}

#login-form input{
    border: solid 1px white;
    border-radius: 4px;
}

#login-form input:focus{
    outline: #609eff solid 1px;
}

#login-button{
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

#login-button button{
    background-color: white;
    color: black;
    border: none;
    padding: 10px 20px;
}


</style>
