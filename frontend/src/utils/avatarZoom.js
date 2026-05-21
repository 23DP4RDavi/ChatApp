const AVATAR_ZOOM_EVENT = 'app:avatar-zoom'

export const openAvatarZoom = (src, label = '') => {
  if (!src || typeof window === 'undefined') return

  window.dispatchEvent(new CustomEvent(AVATAR_ZOOM_EVENT, {
    detail: {
      src,
      label,
    },
  }))
}

export const avatarZoomEventName = AVATAR_ZOOM_EVENT
