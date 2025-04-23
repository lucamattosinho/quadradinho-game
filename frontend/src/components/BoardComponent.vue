<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center p-4 space-y-8">
    <!-- Tabuleiro -->
    <div class="grid grid-rows-4 gap-2 bg-white rounded-xl p-4 shadow-xl border border-indigo-200">
      <div
        v-for="(row, rowIndex) in board"
        :key="rowIndex"
        class="grid grid-cols-4 gap-2"
      >
        <button
          v-for="(letter, colIndex) in row"
          :key="`${rowIndex}-${colIndex}`"
          class="w-16 h-16 rounded-xl text-2xl font-extrabold flex items-center justify-center transition-all duration-200 border shadow-sm"
          :class="{
            'bg-indigo-500 text-white border-indigo-600 scale-110': isSelectedCoord(colIndex, rowIndex),
            'bg-white hover:bg-indigo-100 text-gray-800 border-gray-300': !isSelectedCoord(colIndex, rowIndex),
          }"
          @click="selectTileCoord(colIndex, rowIndex)"
        >
          {{ letter }}
        </button> 
      </div>
    </div>

    <!-- Palavra sendo montada -->
    <div class="text-2xl font-semibold text-gray-700">
      Palavra: <span class="text-indigo-600">{{ currentWord }}</span>
    </div>

    <!-- Botão enviar -->
    <button
      class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2 rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
      @click="submitWord"
      :disabled="path.length < 4"
    >
      Enviar Palavra
    </button>

    <!-- Resultado -->
    <div v-if="validationResult !== null" class="mt-2 text-xl font-semibold">
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
  const pendingStartLetters = ref([]) // para guardar múltiplas posições iniciais possíveis
  const previousKey = ref(null)       // para lembrar qual foi a tecla anterior
  
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

    if (path.value.length === 0) {
      path.value.push({ x, y })
      previousKey.value = board.value[y][x]
      return
    }

    const last = path.value[path.value.length - 1]
    const neighbors = getNeighbors(last)

    const isNeighbor = neighbors.some(pos => pos.x === x && pos.y === y)

    if (isNeighbor) {
      path.value.push({ x, y })
      previousKey.value = board.value[y][x]
    } else {
      console.log(`(${x}, ${y}) não é vizinho de (${last.x}, ${last.y})`)
    }
  }
  const isSelected = (index) => {
    return path.value.includes(index)
  }
  
  const selectTile = (index) => {
    if (isSelected(index)) return // evita seleção repetida
    path.value.push(index)
  }

  const findPathForWord = (word) => {
    const rows = board.value.length
    const cols = board.value[0].length

    const visited = Array.from({ length: rows }, () =>
      Array(cols).fill(false)
    )

    const path = []

    const dfs = (x, y, index) => {
      if (index === word.length) return true
      if (
        x < 0 || y < 0 || x >= cols || y >= rows ||
        visited[y][x] || board.value[y][x] !== word[index]
      ) return false

      visited[y][x] = true
      path.push({ x, y })

      const directions = [
        { x: -1, y: 0 },
        { x: 1, y: 0 },
        { x: 0, y: -1 },
        { x: 0, y: 1 },
      ]

      for (const dir of directions) {
        if (dfs(x + dir.x, y + dir.y, index + 1)) return true
      }

      visited[y][x] = false
      path.pop()
      return false
    }

    for (let y = 0; y < rows; y++) {
      for (let x = 0; x < cols; x++) {
        if (board.value[y][x] === word[0]) {
          path.length = 0
          visited.forEach(row => row.fill(false))
          if (dfs(x, y, 0)) return [...path]
        }
      }
    }

    return null
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

  const typedLetters = ref("")

  const handleKeyPress = (event) => {
    const key = event.key.toUpperCase()

    if (!/^[A-Z]$/.test(key) && key !== 'ENTER' && key !== 'BACKSPACE') return
    if (!board.value.length) return

    if (key === 'ENTER') {
      if (path.value.length >= 4) {
        submitWord()
      }
      typedLetters.value = ""
      return
    }

    if (key === 'BACKSPACE') {
      typedLetters.value = typedLetters.value.slice(0, -1)
      path.value = findPathForWord(typedLetters.value) || []
      return
    }

    typedLetters.value += key

    const newPath = findPathForWord(typedLetters.value)
    if (newPath) {
      path.value = newPath
    } else {
      console.log("Caminho inválido")
    }
  }

  
  </script>


  
