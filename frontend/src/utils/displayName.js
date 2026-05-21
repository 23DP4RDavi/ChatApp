export const getUserDisplayName = (user, fallback = 'Anonymous') => {
  if (!user) return fallback
  return user.username || user.name || fallback
}

export const getUserInitial = (user, fallback = '?') => {
  const label = getUserDisplayName(user, fallback)
  return label.charAt(0).toUpperCase() || fallback
}

export const getUserHandle = (user) => {
  if (!user?.username) return ''
  return `@${user.username}`
}
