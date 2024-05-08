<template>
  <div class="d-flex justify-content-center align-items-center flex-column">
    <canvas ref="gameCanvas" id="gameCanvas" width="800" height="600"></canvas>
    <div>Time remaining: {{ timer }}</div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
export default {
  setup() {
    const route = useRoute();
    const gameCanvas = ref(null);
    const playerPos = ref({ x: 0, y: 0 });
    const trail = ref([]);
    const socket = ref(null);
    const otherPlayers = ref([]);
    //const filledAreas = ref([]);
    const timer = ref(route.params.timer);
    let intervalId = null;
    const router = useRouter();
    const points = ref([]);
    const otherPlayersPoints = ref([]);

    onMounted(() => {
      const ctx = gameCanvas.value.getContext('2d');

      playerPos.value = {
        x: Math.floor(Math.random() * ((800 / 5) - 0) + 0) * 5,
        y: Math.floor(Math.random() * ((600 / 5) - 0) + 0) * 5,
      };

      socket.value = new WebSocket("ws://node64.webte.fei.stuba.sk:2346");

      socket.value.onopen = (event) => {
        console.log('WebSocket connection established');
        socket.value.send(JSON.stringify({ timer: timer.value }));

      };

      socket.value.onmessage = (event) => {
        console.log('Message from server: ', event.data);

        const incomingData = JSON.parse(event.data);

        if (incomingData.timer !== undefined) {
          timer.value = incomingData.timer;
        } else if (incomingData.uuid && !socket.value.uuid) {
          socket.value.uuid = incomingData.uuid;
          socket.value.color = incomingData.color;

          //GENERATING RANDOM POINTS
          for (let i = 0; i < 3; i++) {
            points.value.push({
              x: Math.floor(Math.random() * ((800 / 5) - 0) + 0) * 5,
              y: Math.floor(Math.random() * ((600 / 5) - 0) + 0) * 5,
              color: darkenColor(socket.value.color, 20)
            });
          }
          console.log(points.value);
          socket.value.send(JSON.stringify({ uuid: socket.value.uuid, points: points.value }));
          console.log(socket.value);
        } else if (incomingData.winner) {
          alert('The winner is: ' + incomingData.winner);

        } else if (incomingData.points && incomingData.uuid !== socket.value.uuid) {

          otherPlayersPoints.value = incomingData.points;
          console.log(otherPlayersPoints.value);
        }
        else {
          const incomingPlayerData = incomingData;

          if (incomingPlayerData.uuid !== socket.value.uuid) {
            console.log('inc ' + incomingPlayerData.uuid)
            console.log('soc ' + socket.value.uuid)

            const index = trail.value.findIndex(pos => pos.x === incomingPlayerData.x && pos.y === incomingPlayerData.y);
            console.log('index: ' + index);

            if (index !== -1) {
              incomingPlayerData.trail.splice(1);
              //incomingPlayerData.trail = [];
              // socket.value.send(JSON.stringify({ uuid: incomingPlayerData.uuid, trail: incomingPlayerData.trail }));
              socket.value.send(JSON.stringify({ uuid: incomingPlayerData.uuid, killed: true }));

            }

            otherPlayers.value.push(incomingPlayerData);
            //requestAnimationFrame(render);
          }
        }

        requestAnimationFrame(render);
      };

      window.addEventListener('keydown', function (event) {
        const prevPos = { ...playerPos.value };

        switch (event.key) {
          case 'ArrowUp':
            playerPos.value.y -= 5;
            break;
          case 'ArrowDown':
            playerPos.value.y += 5;
            break;
          case 'ArrowLeft':
            playerPos.value.x -= 5;
            break;
          case 'ArrowRight':
            playerPos.value.x += 5;
            break;
        }

        trail.value.push({ ...playerPos.value });
        console.log(trail);

        socket.value.send(JSON.stringify({ x: playerPos.value.x, y: playerPos.value.y, trail: trail.value }));
        socket.value.send(JSON.stringify({ uuid: socket.value.uuid, points: points.value }));

        //socket.value.send(JSON.stringify({ x: playerPos.value.x, y: playerPos.value.y }));
      });


      function render() {
        ctx.clearRect(0, 0, 800, 600);

        otherPlayers.value.forEach((player) => {
          ctx.fillStyle = player.color;
          ctx.fillRect(player.x, player.y, 20, 20);

          if (player.trail) {
            player.trail.forEach((pos) => {
              ctx.fillStyle = player.color;
              ctx.fillRect(pos.x, pos.y, 20, 20);
            });
          }
        });

        ctx.fillStyle = socket.value.color;
        ctx.fillRect(playerPos.value.x, playerPos.value.y, 20, 20);

        trail.value.forEach((pos) => {
          ctx.fillStyle = socket.value.color;
          ctx.fillRect(pos.x, pos.y, 20, 20);
        });

        points.value = points.value.filter((point) => {
          if (checkCollision(playerPos.value, point)) {

            return false;
          }
          return true;
        });

        points.value.forEach((point) => {
          ctx.fillStyle = point.color;
          ctx.fillRect(point.x, point.y, 20, 20);
        });

        otherPlayersPoints.value.forEach((point) => {
          ctx.fillStyle = point.color;
          ctx.fillRect(point.x, point.y, 20, 20);
        });
      }

      function darkenColor(color, percent) {
        const num = parseInt(color.replace("#", ""), 16),
          amt = Math.round(2.55 * percent),
          R = (num >> 16) + amt,
          B = ((num >> 8) & 0x00FF) + amt,
          G = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 + (B < 255 ? B < 1 ? 0 : B : 255) * 0x100 + (G < 255 ? G < 1 ? 0 : G : 255)).toString(16).slice(1);
      }

      function checkCollision(player, point) {
        return player.x === point.x && player.y === point.y;
      }

    });

    onUnmounted(() => {
      clearInterval(intervalId);
      socket.value.close();
    });

    return { gameCanvas, playerPos, timer };
  },
};


/*if (trail.value.some(pos => pos.x === playerPos.value.x && pos.y === playerPos.value.y)) {
  ctx.fillStyle = '#f00';
  ctx.beginPath();
  ctx.moveTo(trail.value[0].x, trail.value[0].y);
  for (let i = 1; i < trail.value.length; i++) {
    ctx.lineTo(trail.value[i].x, trail.value[i].y);
  }
  ctx.closePath();
  ctx.fill();
  filledAreas.value.push([...trail.value]);
  socket.value.send(JSON.stringify({ trail: trail.value }));
}*/

</script>

<style scoped>
canvas {

  border: 2px solid black;
}
</style>