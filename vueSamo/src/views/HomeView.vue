<template>

  <body style="height: 100vh; display: flex; justify-content: center; align-items: center;">
    <div class="d-flex flex-column align-items-center w-100">
      <h1 class="text-center fw-bold">Vitaj na zadani 3 !</h1>
      <div class="input-group mb-3 w-50">
        <span class="input-group-text" id="basic-addon1">@</span>
        <input type="number" id="ageInput" min="30" max="120" class="form-control" placeholder="Timer"
          aria-label="Username" aria-describedby="basic-addon1" v-model="timer">
      </div>
      <button type="button" class="btn btn-light w-25" @click="play">Play!</button>
    </div>
  </body>
</template>


<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

export default {
  setup() {
    const userName = ref('');
    //const socket = ref(null);
    const timer = ref(null);
    const router = useRouter();

    const play = () => {
      // Establish the WebSocket connection
      /* socket.value = new WebSocket("ws://localhost:2346");
 
       // Set up the onmessage event handler
       socket.value.onmessage = function (event) {
         // Parse the incoming message as JSON
         let message = JSON.parse(event.data);
 
         // Check the type of the message
         if (message.type === 'newPlayer') {
           console.log('A new player has connected: ', message.data);
         } else if (message.type === 'updatePlayer') {
           console.log('A player has updated their data: ', message.data);
         }*/

      router.push({ name: 'about', params: { timer: timer.value } });
    };

    /*socket.value.onopen = (event) => {
      let name = userName.value;

      let positionX = Math.random() * 100;
      let positionY = Math.random() * 100;
      let color = '#' + Math.floor(Math.random() * 16777215).toString(16);

      socket.value.send(JSON.stringify({ name, positionX, positionY, color }));
    };*/

    onMounted(() => {
      let ageInput = document.getElementById('ageInput');

      ageInput.addEventListener('keydown', (event) => {
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) {
          event.preventDefault();
        }
      });
    });

    return { userName, play, timer, play };
  },
}
</script>




<style scoped>
body {
  background-color: rgb(150, 207, 248);
}

h1 {
  color: aliceblue;
}
</style>
