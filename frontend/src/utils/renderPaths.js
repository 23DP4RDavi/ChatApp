/**
 * Shared canvas path renderer — handles stroke, fill, spray, marker, square
 * Used by Gallery, DrawingCard, and Messages.
 *
 * @param {CanvasRenderingContext2D} ctx
 * @param {HTMLCanvasElement} canvas
 * @param {Array} paths  — array of path objects saved by Draw.vue
 */
export function renderPaths(ctx, canvas, paths) {
  ctx.fillStyle = '#FFFFFF'
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  if (!Array.isArray(paths)) return

  paths.forEach((path) => {
    // ── Flood fill ──────────────────────────────────────────────────────────
    if (path.type === 'fill') {
      const [fr, fg, fb] = hexToRgb(path.color || '#000000')
      const x = Math.round(clamp(path.x, 0, canvas.width - 1))
      const y = Math.round(clamp(path.y, 0, canvas.height - 1))
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
      const data = imageData.data
      const si = (y * canvas.width + x) * 4
      const [tr, tg, tb] = [data[si], data[si + 1], data[si + 2]]
      if (tr === fr && tg === fg && tb === fb) return
      const matches = (i) =>
        Math.abs(data[i] - tr) < 32 &&
        Math.abs(data[i + 1] - tg) < 32 &&
        Math.abs(data[i + 2] - tb) < 32
      const visited = new Uint8Array(canvas.width * canvas.height)
      const stack = [[x, y]]
      while (stack.length > 0) {
        const [cx, cy] = stack.pop()
        if (cx < 0 || cx >= canvas.width || cy < 0 || cy >= canvas.height) continue
        const vi = cy * canvas.width + cx
        if (visited[vi]) continue
        const pi = vi * 4
        if (!matches(pi)) continue
        visited[vi] = 1
        data[pi] = fr; data[pi + 1] = fg; data[pi + 2] = fb; data[pi + 3] = 255
        stack.push([cx + 1, cy], [cx - 1, cy], [cx, cy + 1], [cx, cy - 1])
      }
      ctx.putImageData(imageData, 0, 0)
      return
    }

    // ── Shapes (line, rectangle, circle, triangle, star, arrow) ───────────
    if (path.type === 'shape') {
      if (!path.shape || !path.start || !path.end) return
      drawShapeOnCtx(ctx, path.shape, path.start, path.end, {
        color: path.color || '#000000',
        width: path.width || 2,
        fill: !!path.fill,
      })
      return
    }

    // ── Image block ─────────────────────────────────────────────────────────
    if (path.type === 'image') {
      if (!path.src || !path.width || !path.height) return
      const img = new Image()
      img.onload = () => {
        ctx.drawImage(img, path.x || 0, path.y || 0, path.width, path.height)
      }
      img.src = path.src
      return
    }

    // ── Text ────────────────────────────────────────────────────────────────
    if (path.type === 'text') {
      if (!path.text) return
      ctx.save()
      ctx.fillStyle = path.color || '#000000'
      ctx.textBaseline = 'top'
      ctx.font = `${path.fontSize || 16}px ${path.fontFamily || 'Nunito, Segoe UI, sans-serif'}`
      ctx.fillText(path.text, path.x || 0, path.y || 0)
      ctx.restore()
      return
    }

    // ── Stroke paths ─────────────────────────────────────────────────────────
    if (!path.points || path.points.length < 2) return
    const bType = path.brushType || 'pen'
    const color = path.color || '#000000'
    const width = path.width || 2

    if (bType === 'spray') {
      ctx.globalAlpha = 0.8
      ctx.fillStyle = color
      path.points.forEach((pt) => {
        if (!Array.isArray(pt.dots)) return
        pt.dots.forEach((dot) => {
          ctx.beginPath()
          ctx.arc(dot.x, dot.y, dot.r, 0, Math.PI * 2)
          ctx.fill()
        })
      })
      ctx.globalAlpha = 1
      return
    }

    ctx.globalAlpha = bType === 'marker' ? 0.35 : 1
    ctx.strokeStyle = color
    ctx.lineWidth = bType === 'marker' ? width * 2.5 : width
    ctx.lineCap = bType === 'square' ? 'square' : 'round'
    ctx.lineJoin = bType === 'square' ? 'miter' : 'round'
    ctx.beginPath()
    path.points.forEach((pt, i) => {
      if (i === 0) ctx.moveTo(pt.x, pt.y)
      else ctx.lineTo(pt.x, pt.y)
    })
    ctx.stroke()
    ctx.globalAlpha = 1
  })
}

function drawShapeOnCtx(ctx, shapeType, start, end, { color, width, fill = false }) {
  ctx.save()
  ctx.strokeStyle = color
  ctx.fillStyle = color
  ctx.lineWidth = width
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'

  if (shapeType === 'line') {
    ctx.beginPath()
    ctx.moveTo(start.x, start.y)
    ctx.lineTo(end.x, end.y)
    ctx.stroke()
    ctx.restore()
    return
  }

  if (shapeType === 'arrow') {
    const dx = end.x - start.x
    const dy = end.y - start.y
    const angle = Math.atan2(dy, dx)
    const headLen = Math.max(10, width * 4)
    ctx.beginPath()
    ctx.moveTo(start.x, start.y)
    ctx.lineTo(end.x, end.y)
    ctx.stroke()
    ctx.beginPath()
    ctx.moveTo(end.x, end.y)
    ctx.lineTo(end.x - headLen * Math.cos(angle - Math.PI / 6), end.y - headLen * Math.sin(angle - Math.PI / 6))
    ctx.lineTo(end.x - headLen * Math.cos(angle + Math.PI / 6), end.y - headLen * Math.sin(angle + Math.PI / 6))
    ctx.closePath()
    ctx.fill()
    ctx.restore()
    return
  }

  const x = Math.min(start.x, end.x)
  const y = Math.min(start.y, end.y)
  const w = Math.abs(end.x - start.x)
  const h = Math.abs(end.y - start.y)

  if (shapeType === 'rectangle') {
    if (fill) ctx.fillRect(x, y, w, h)
    ctx.strokeRect(x, y, w, h)
    ctx.restore()
    return
  }

  if (shapeType === 'circle') {
    ctx.beginPath()
    ctx.ellipse(x + w / 2, y + h / 2, Math.max(1, w / 2), Math.max(1, h / 2), 0, 0, Math.PI * 2)
    if (fill) ctx.fill()
    ctx.stroke()
    ctx.restore()
    return
  }

  const drawPolygon = (points) => {
    if (!points.length) return
    ctx.beginPath()
    ctx.moveTo(points[0].x, points[0].y)
    for (let i = 1; i < points.length; i++) ctx.lineTo(points[i].x, points[i].y)
    ctx.closePath()
    if (fill) ctx.fill()
    ctx.stroke()
  }

  if (shapeType === 'triangle') {
    drawPolygon([
      { x: x + w / 2, y },
      { x, y: y + h },
      { x: x + w, y: y + h },
    ])
    ctx.restore()
    return
  }

  if (shapeType === 'star') {
    const cx = x + w / 2
    const cy = y + h / 2
    const outer = Math.max(1, Math.min(w, h) / 2)
    const inner = outer * 0.45
    const points = []
    for (let i = 0; i < 10; i++) {
      const r = i % 2 === 0 ? outer : inner
      const a = (-Math.PI / 2) + (i * Math.PI / 5)
      points.push({ x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) })
    }
    drawPolygon(points)
  }

  ctx.restore()
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function hexToRgb(hex) {
  const h = hex.replace('#', '')
  return [
    parseInt(h.slice(0, 2), 16),
    parseInt(h.slice(2, 4), 16),
    parseInt(h.slice(4, 6), 16),
  ]
}

function clamp(v, lo, hi) {
  return Math.min(Math.max(v, lo), hi)
}
