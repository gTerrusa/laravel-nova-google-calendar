export const slugify = (string) => {
  return string.toLowerCase()
    .replace(/[^\w ]+/g, '')
    .replace(/ +/g, '-')
}
