const KEY = 'PKWKEY2025'

function xor(str: string): string {
  return str.split('').map((c, i) =>
    String.fromCharCode(c.charCodeAt(0) ^ KEY.charCodeAt(i % KEY.length))
  ).join('')
}

export function encodeSave(data: object): string {
  return btoa(xor(JSON.stringify(data)))
}

export function decodeSave(encoded: string): object {
  return JSON.parse(xor(atob(encoded)))
}
