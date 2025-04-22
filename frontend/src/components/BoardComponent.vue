<template>
    <div class="flex flex-col items-center space-y-6">
      <div class="grid grid-rows-4 gap-2">
        <div
          v-for="(row, rowIndex) in board"
          :key="rowIndex"
          class="grid grid-cols-4 gap-2"
        >
          <button
            v-for="(letter, colIndex) in row"
            :key="`${rowIndex}-${colIndex}`"
            class="w-16 h-16 text-2xl font-bold rounded-lg border border-gray-300 flex items-center justify-center transition hover:bg-blue-100"
            :class="{
              'bg-blue-300 text-white': isSelectedCoord(colIndex, rowIndex),
            }"
            @click="selectTileCoord(colIndex, rowIndex)"
          >
            {{ letter }}
          </button>
        </div>
      </div>
  
      <div class="text-xl font-semibold">Palavra: {{ currentWord }}</div>
  
      <button
        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
        @click="submitWord"
        :disabled="path.length < 4"
      >
        Enviar Palavra
      </button>
  
      <div v-if="validationResult !== null" class="mt-4 text-lg">
        Resultado:
        <span :class="validationResult ? 'text-green-600' : 'text-red-600'">
          {{ validationResult ? 'Válida ✅' : 'Inválida ❌' }}
        </span>
      </div>
    </div>
  </template>
  
  
  <script setup>
  import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
  import axios from 'axios'
  
  // Simulando um tabuleiro fixo (pode vir do backend futuramente)
  const board = ref([])
  
  const path = ref([]) // Armazena os índices dos botões selecionados
  const validationResult = ref(null)
  
  onMounted(() => {
    fetchBoard(),
    window.addEventListener('keydown', handleKeyPress)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyPress)
  })


  const flatBoard = computed(() => board.value.flat())
  
  const currentWord = computed(() => {
    return path.value.map(({ x, y }) => board.value[y][x]).join('')
  })

  const isSelectedCoord = (x, y) => {
    return path.value.some(p => p.x === x && p.y === y)
  }
  const selectTileCoord = (x, y) => {
    if (isSelectedCoord(x, y)) return
        path.value.push({ x, y })
  }
  const isSelected = (index) => {
    return path.value.includes(index)
  }
  
  const selectTile = (index) => {
    if (isSelected(index)) return // evita seleção repetida
    path.value.push(index)
  }

  const fetchBoard = async () => {
    try {
        const response = await axios.get('http://localhost:8000/api/board') // Endpoint que vai retornar o tabuleiro
        board.value = response.data.board
    } catch (err) {
        console.error('Erro ao carregar o tabuleiro:', err)
    }
  }
  
  const submitWord = async () => {
    const coordPath = [...path.value]
    const simplePath = JSON.parse(JSON.stringify(coordPath));
    console.log(`A palavra é: ${currentWord.value}`)
    try {
      const response = await axios.post('http://localhost:8000/api/validate-word', {
        word: currentWord.value,
        path: simplePath,
      })
      console.log(`A resposta é: ${response.data.valid} porque ${response.data.reason}`)
      validationResult.value = response.data.valid
    } catch (err) {
      console.error('Erro ao validar palavra:', err)
      validationResult.value = true
    }
  }

  const getNeighbors = ({ x, y }) => {
    return [
      { x: x - 1, y }, // esquerda
      { x: x + 1, y }, // direita
      { x, y: y - 1 }, // cima
      { x, y: y + 1 }  // baixo
    ].filter(pos =>
      pos.x >= 0 &&
      pos.y >= 0 &&
      pos.y < board.value.length &&
      pos.x < board.value[0].length &&
      !isSelectedCoord(pos.x, pos.y)
    )
  }

const handleKeyPress = (event) => {
  const key = event.key.toUpperCase()

  // Verifica se é Enter
  if (key === 'ENTER' && path.value.length >= 4) {
    submitWord()
    return
  }

  // Verifica se é Backspace
  if (key === 'BACKSPACE') {
    path.value.pop()
    return
  }

  // Primeira letra: procurar em todo o grid
  if (path.value.length === 0) {
    // Adiciona todas as posições possíveis da primeira letra
    const positions = []
    for (let y = 0; y < board.value.length; y++) {
      for (let x = 0; x < board.value[y].length; x++) {
        if (board.value[y][x] === key && !isSelectedCoord(x, y)) {
          positions.push({ x, y })
        }
      }
    }

    // Se houver mais de uma possibilidade, use uma heurística ou permita escolha futura
    if (positions.length > 0) {
      path.value.push(positions[0]) // por enquanto, ainda escolhemos a primeira
    }
  } else {
    // Próxima letra: buscar apenas nos vizinhos do último
    const last = path.value[path.value.length - 1]
    const neighbors = getNeighbors(last)

    for (const neighbor of neighbors) {
      if (board.value[neighbor.y][neighbor.x] === key) {
        path.value.push(neighbor)
        return
      }
    }
  }
}

  
  </script>


  
