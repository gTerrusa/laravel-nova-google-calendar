export const tryJsonParse = (value) => {
  try {
    const parsed = JSON.parse(String(value))

    if (parsed && typeof parsed === 'object') {
      return parsed
    }
  } catch (e) { }

  return value
}
