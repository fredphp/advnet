/**
 * 数据加密解密工具
 *
 * 使用 AES-256-CBC 对后端返回的加密数据进行解密
 * 密钥和 IV 必须与后端 DataEncryptService 保持一致
 *
 * 后端加密流程：JSON → AES-256-CBC → Base64
 * 前端解密流程：Base64 → AES-256-CBC → JSON
 */

// ★ 密钥（32字节），必须与后端 DataEncryptService::getKey() 一致
const AES_KEY = 'advnet2024secure@aes256key!#data'

// ★ 初始化向量（16字节），必须与后端 DataEncryptService::getIv() 一致
const AES_IV = 'advnet@iv16byte'

/**
 * AES-256-CBC 解密
 *
 * @param {string} encryptedData Base64 编码的密文
 * @returns {Object|null} 解密后的数据对象，失败返回 null
 */
export function decryptData(encryptedData) {
	if (!encryptedData) {
		return null
	}

	// 如果不是字符串，说明未加密，直接返回
	if (typeof encryptedData !== 'string') {
		return encryptedData
	}

	try {
		// Base64 解码
		const base64 = base64Decode(encryptedData)
		if (!base64) {
			console.warn('[Crypto] Base64解码失败，尝试直接解析JSON')
			return JSON.parse(encryptedData)
		}

		// AES-256-CBC 解密
		const decrypted = aesDecrypt(base64, AES_KEY, AES_IV)
		if (!decrypted) {
			console.warn('[Crypto] AES解密失败，尝试直接解析JSON')
			return JSON.parse(encryptedData)
		}

		// JSON 解析
		const result = JSON.parse(decrypted)
		console.log('[Crypto] 数据解密成功')
		return result
	} catch (e) {
		console.warn('[Crypto] 解密异常，尝试直接解析JSON:', e.message)
		try {
			return JSON.parse(encryptedData)
		} catch (e2) {
			console.error('[Crypto] 数据解析完全失败')
			return null
		}
	}
}

/**
 * AES-256-CBC 加密（用于测试/调试）
 *
 * @param {Object} data 待加密的数据
 * @returns {string} Base64 编码的密文
 */
export function encryptData(data) {
	const json = JSON.stringify(data)
	const encrypted = aesEncrypt(json, AES_KEY, AES_IV)
	if (encrypted) {
		return base64Encode(encrypted)
	}
	return ''
}

// ==================== 内部工具方法 ====================

/**
 * AES-256-CBC 解密（跨平台实现）
 */
function aesDecrypt(data, key, iv) {
	// #ifdef H5
	// H5 使用 Web Crypto API
	return aesDecryptH5(data, key, iv)
	// #endif

	// #ifdef APP-PLUS
	// APP 使用 plus.crypto
	return aesDecryptApp(data, key, iv)
	// #endif

	// #ifdef MP-WEIXIN
	// 小程序使用纯 JS 实现
	return aesDecryptJS(data, key, iv)
	// #endif

	// 其他平台使用纯 JS
	return aesDecryptJS(data, key, iv)
}

/**
 * AES-256-CBC 加密（跨平台实现）
 */
function aesEncrypt(data, key, iv) {
	// #ifdef H5
	return aesEncryptH5(data, key, iv)
	// #endif

	// #ifdef APP-PLUS
	return aesEncryptApp(data, key, iv)
	// #endif

	return aesEncryptJS(data, key, iv)
}

// ==================== H5 平台实现 ====================

function aesDecryptH5(data, key, iv) {
	const crypto = window.crypto || window.msCrypto
	if (!crypto || !crypto.subtle) {
		console.warn('[Crypto] Web Crypto API 不可用，降级使用JS解密')
		return aesDecryptJS(data, key, iv)
	}

	const keyData = stringToArrayBuffer(key)
	const ivData = stringToArrayBuffer(iv)

	try {
		// crypto.subtle 是异步的，这里用同步方式获取
		// 由于 Web Crypto API 是异步的，H5 环境下使用 CryptoJS 或降级 JS
		// 这里降级到 JS 实现
		return aesDecryptJS(data, key, iv)
	} catch (e) {
		console.error('[Crypto] H5解密失败:', e)
		return null
	}
}

function aesEncryptH5(data, key, iv) {
	return aesEncryptJS(data, key, iv)
}

// ==================== APP-PLUS 平台实现 ====================

function aesDecryptApp(data, key, iv) {
	try {
		if (typeof plus !== 'undefined' && plus.crypto) {
			const result = plus.crypto.decryptAES256CBC({
				data: data,
				key: key,
				iv: iv,
				padding: 'PKCS#7'
			})
			return result
		}
	} catch (e) {
		console.warn('[Crypto] APP plus.crypto 解密失败，降级使用JS:', e.message)
	}
	return aesDecryptJS(data, key, iv)
}

function aesEncryptApp(data, key, iv) {
	try {
		if (typeof plus !== 'undefined' && plus.crypto) {
			const result = plus.crypto.encryptAES256CBC({
				data: data,
				key: key,
				iv: iv,
				padding: 'PKCS#7'
			})
			return result
		}
	} catch (e) {
		console.warn('[Crypto] APP plus.crypto 加密失败，降级使用JS:', e.message)
	}
	return aesEncryptJS(data, key, iv)
}

// ==================== 纯 JS 实现（Fallback） ====================

/**
 * AES S-Box
 */
const SBOX = [
	0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76,
	0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0,
	0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15,
	0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75,
	0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84,
	0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf,
	0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8,
	0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2,
	0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73,
	0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb,
	0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79,
	0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08,
	0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a,
	0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e,
	0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf,
	0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16
]

const INV_SBOX = [
	0x52, 0x09, 0x6a, 0xd5, 0x30, 0x36, 0xa5, 0x38, 0xbf, 0x40, 0xa3, 0x9e, 0x81, 0xf3, 0xd7, 0xfb,
	0x7c, 0xe3, 0x39, 0x82, 0x9b, 0x2f, 0xff, 0x87, 0x34, 0x8e, 0x43, 0x44, 0xc4, 0xde, 0xe9, 0xcb,
	0x54, 0x7b, 0x94, 0x32, 0xa6, 0xc2, 0x23, 0x3d, 0xee, 0x4c, 0x95, 0x0b, 0x42, 0xfa, 0xc3, 0x4e,
	0x08, 0x2e, 0xa1, 0x66, 0x28, 0xd9, 0x24, 0xb2, 0x76, 0x5b, 0xa2, 0x49, 0x6d, 0x8b, 0xd1, 0x25,
	0x72, 0xf8, 0xf6, 0x64, 0x86, 0x68, 0x98, 0x16, 0xd4, 0xa4, 0x5c, 0xcc, 0x5d, 0x65, 0xb6, 0x92,
	0x6c, 0x70, 0x48, 0x50, 0xfd, 0xed, 0xb9, 0xda, 0x5e, 0x15, 0x46, 0x57, 0xa7, 0x8d, 0x9d, 0x84,
	0x90, 0xd8, 0xab, 0x00, 0x8c, 0xbc, 0xd3, 0x0a, 0xf7, 0xe4, 0x58, 0x05, 0xb8, 0xb3, 0x45, 0x06,
	0xd0, 0x2c, 0x1e, 0x8f, 0xca, 0x3f, 0x0f, 0x02, 0xc1, 0xaf, 0xbd, 0x03, 0x01, 0x13, 0x8a, 0x6b,
	0x3a, 0x91, 0x11, 0x41, 0x4f, 0x67, 0xdc, 0xea, 0x97, 0xf2, 0xcf, 0xce, 0xf0, 0xb4, 0xe6, 0x73,
	0x96, 0xac, 0x74, 0x22, 0xe7, 0xad, 0x35, 0x85, 0xe2, 0xf9, 0x37, 0xe8, 0x1c, 0x75, 0xdf, 0x6e,
	0x47, 0xf1, 0x1a, 0x71, 0x1d, 0x29, 0xc5, 0x89, 0x6f, 0xb7, 0x62, 0x0e, 0xaa, 0x18, 0xbe, 0x1b,
	0xfc, 0x56, 0x3e, 0x4b, 0xc6, 0xd2, 0x79, 0x20, 0x9a, 0xdb, 0xc0, 0xfe, 0x78, 0xcd, 0x5a, 0xf4,
	0x1f, 0xdd, 0xa8, 0x33, 0x88, 0x07, 0xc7, 0x31, 0xb1, 0x12, 0x10, 0x59, 0x27, 0x80, 0xec, 0x5f,
	0x60, 0x51, 0x7f, 0xa9, 0x19, 0xb5, 0x4a, 0x0d, 0x2d, 0xe5, 0x7a, 0x9f, 0x93, 0xc9, 0x9c, 0xef,
	0xa0, 0xe0, 0x3b, 0x4d, 0xae, 0x2a, 0xf5, 0xb0, 0xc8, 0xeb, 0xbb, 0x3c, 0x83, 0x53, 0x99, 0x61,
	0x17, 0x2b, 0x04, 0x7e, 0xba, 0x77, 0xd6, 0x26, 0xe1, 0x69, 0x14, 0x63, 0x55, 0x21, 0x0c, 0x7d
]

const RCON = [0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x80, 0x1b, 0x36]

/**
 * AES-256-CBC 解密（纯 JS 实现）
 * 使用 PKCS#7 去填充
 */
function aesDecryptJS(cipherData, keyStr, ivStr) {
	try {
		// Base64 解码
		const bytes = base64ToBytes(cipherData)
		if (!bytes || bytes.length === 0 || bytes.length % 16 !== 0) {
			return null
		}

		const key = stringToBytes(keyStr)
		const iv = stringToBytes(ivStr)

		// 密钥扩展
		const expandedKey = expandKey(key)

		// CBC 解密每个块
		let plaintext = []
		let prevBlock = iv

		for (let i = 0; i < bytes.length; i += 16) {
			const block = bytes.slice(i, i + 16)
			const decrypted = decryptBlock(block, expandedKey)

			// XOR with previous ciphertext block (CBC)
			for (let j = 0; j < 16; j++) {
				plaintext.push(decrypted[j] ^ prevBlock[j])
			}
			prevBlock = block
		}

		// PKCS#7 去填充
		const padLen = plaintext[plaintext.length - 1]
		if (padLen < 1 || padLen > 16) {
			return null
		}
		for (let i = 0; i < padLen; i++) {
			if (plaintext[plaintext.length - 1 - i] !== padLen) {
				return null
			}
		}
		plaintext = plaintext.slice(0, plaintext.length - padLen)

		// 转为字符串
		return bytesToString(plaintext)
	} catch (e) {
		console.error('[Crypto] JS解密失败:', e)
		return null
	}
}

/**
 * AES-256-CBC 加密（纯 JS 实现）
 */
function aesEncryptJS(plainStr, keyStr, ivStr) {
	try {
		let bytes = stringToBytes(plainStr)

		// PKCS#7 填充
		const padLen = 16 - (bytes.length % 16)
		for (let i = 0; i < padLen; i++) {
			bytes.push(padLen)
		}

		const key = stringToBytes(keyStr)
		const iv = stringToBytes(ivStr)

		const expandedKey = expandKey(key)

		let ciphertext = []
		let prevBlock = iv

		for (let i = 0; i < bytes.length; i += 16) {
			const block = bytes.slice(i, i + 16)
			// XOR with previous block
			const xored = []
			for (let j = 0; j < 16; j++) {
				xored.push(block[j] ^ prevBlock[j])
			}
			const encrypted = encryptBlock(xored, expandedKey)
			ciphertext = ciphertext.concat(encrypted)
			prevBlock = encrypted
		}

		return bytesToBase64(ciphertext)
	} catch (e) {
		console.error('[Crypto] JS加密失败:', e)
		return ''
	}
}

// ==================== AES 核心运算 ====================

function expandKey(key) {
	const keyLen = key.length
	const nk = keyLen / 4  // 8 for AES-256
	const nb = 4
	const nr = nk + 6      // 14 rounds for AES-256
	const expandedKeySize = nb * (nr + 1) * 4
	const w = []

	let temp = []

	for (let i = 0; i < nk; i++) {
		w[i] = [key[4 * i], key[4 * i + 1], key[4 * i + 2], key[4 * i + 3]]
	}

	for (let i = nk; i < nb * (nr + 1); i++) {
		temp = [...w[i - 1]]
		if (i % nk === 0) {
			// RotWord
			temp = [temp[1], temp[2], temp[3], temp[0]]
			// SubWord
			temp = [SBOX[temp[0]], SBOX[temp[1]], SBOX[temp[2]], SBOX[temp[3]]]
			// XOR with Rcon
			temp[0] ^= RCON[i / nk]
		} else if (nk > 6 && i % nk === 4) {
			temp = [SBOX[temp[0]], SBOX[temp[1]], SBOX[temp[2]], SBOX[temp[3]]]
		}
		w[i] = [
			w[i - nk][0] ^ temp[0],
			w[i - nk][1] ^ temp[1],
			w[i - nk][2] ^ temp[2],
			w[i - nk][3] ^ temp[3]
		]
	}

	return w
}

function addRoundKey(state, roundKeys, round) {
	for (let i = 0; i < 4; i++) {
		const offset = round * 4 + i
		state[i] ^= roundKeys[offset]
	}
}

function subBytes(state, sbox) {
	for (let i = 0; i < 16; i++) {
		state[i] = sbox[state[i]]
	}
}

function shiftRows(state) {
	// Row 1: shift left 1
	let t = state[1]; state[1] = state[5]; state[5] = state[9]; state[9] = state[13]; state[13] = t
	// Row 2: shift left 2
	t = state[2]; state[2] = state[10]; state[10] = t
	t = state[6]; state[6] = state[14]; state[14] = t
	// Row 3: shift left 3
	t = state[3]; state[3] = state[15]; state[15] = state[11]; state[11] = state[7]; state[7] = t
}

function invShiftRows(state) {
	// Row 1: shift right 1
	let t = state[1]; state[1] = state[13]; state[13] = state[9]; state[9] = state[5]; state[5] = t
	// Row 2: shift right 2
	t = state[2]; state[2] = state[10]; state[10] = t
	t = state[6]; state[6] = state[14]; state[14] = t
	// Row 3: shift right 3
	t = state[3]; state[3] = state[7]; state[7] = state[11]; state[11] = state[15]; state[15] = t
}

function mixColumns(state) {
	for (let i = 0; i < 4; i++) {
		const s0 = state[i * 4]
		const s1 = state[i * 4 + 1]
		const s2 = state[i * 4 + 2]
		const s3 = state[i * 4 + 3]

		state[i * 4] = gmul(s0, 2) ^ gmul(s1, 3) ^ s2 ^ s3
		state[i * 4 + 1] = s0 ^ gmul(s1, 2) ^ gmul(s2, 3) ^ s3
		state[i * 4 + 2] = s0 ^ s1 ^ gmul(s2, 2) ^ gmul(s3, 3)
		state[i * 4 + 3] = gmul(s0, 3) ^ s1 ^ s2 ^ gmul(s3, 2)
	}
}

function invMixColumns(state) {
	for (let i = 0; i < 4; i++) {
		const s0 = state[i * 4]
		const s1 = state[i * 4 + 1]
		const s2 = state[i * 4 + 2]
		const s3 = state[i * 4 + 3]

		state[i * 4] = gmul(s0, 14) ^ gmul(s1, 11) ^ gmul(s2, 13) ^ gmul(s3, 9)
		state[i * 4 + 1] = gmul(s0, 9) ^ gmul(s1, 14) ^ gmul(s2, 11) ^ gmul(s3, 13)
		state[i * 4 + 2] = gmul(s0, 13) ^ gmul(s1, 9) ^ gmul(s2, 14) ^ gmul(s3, 11)
		state[i * 4 + 3] = gmul(s0, 11) ^ gmul(s1, 13) ^ gmul(s2, 9) ^ gmul(s3, 14)
	}
}

function gmul(a, b) {
	let p = 0
	for (let i = 0; i < 8; i++) {
		if (b & 1) p ^= a
		const hi = a & 0x80
		a = (a << 1) & 0xff
		if (hi) a ^= 0x1b
		b >>= 1
	}
	return p
}

function encryptBlock(input, expandedKey) {
	const state = [...input]
	const nr = 14 // AES-256 rounds

	addRoundKey(state, expandedKey, 0)

	for (let round = 1; round < nr; round++) {
		subBytes(state, SBOX)
		shiftRows(state)
		mixColumns(state)
		addRoundKey(state, expandedKey, round)
	}

	subBytes(state, SBOX)
	shiftRows(state)
	addRoundKey(state, expandedKey, nr)

	return state
}

function decryptBlock(input, expandedKey) {
	const state = [...input]
	const nr = 14 // AES-256 rounds

	addRoundKey(state, expandedKey, nr)

	for (let round = nr - 1; round > 0; round--) {
		invShiftRows(state)
		subBytes(state, INV_SBOX)
		addRoundKey(state, expandedKey, round)
		invMixColumns(state)
	}

	invShiftRows(state)
	subBytes(state, INV_SBOX)
	addRoundKey(state, expandedKey, 0)

	return state
}

// ==================== 编码工具 ====================

function stringToBytes(str) {
	const bytes = []
	for (let i = 0; i < str.length; i++) {
		bytes.push(str.charCodeAt(i) & 0xff)
	}
	return bytes
}

function bytesToString(bytes) {
	let str = ''
	for (let i = 0; i < bytes.length; i++) {
		str += String.fromCharCode(bytes[i])
	}
	return decodeURIComponent(escape(str))
}

function base64ToBytes(base64) {
	const raw = atob(base64)
	const bytes = []
	for (let i = 0; i < raw.length; i++) {
		bytes.push(raw.charCodeAt(i))
	}
	return bytes
}

function bytesToBase64(bytes) {
	let binary = ''
	for (let i = 0; i < bytes.length; i++) {
		binary += String.fromCharCode(bytes[i])
	}
	return btoa(binary)
}

function base64Decode(str) {
	try {
		return atob(str)
	} catch (e) {
		return null
	}
}

function base64Encode(str) {
	try {
		return btoa(str)
	} catch (e) {
		return ''
	}
}

function stringToArrayBuffer(str) {
	const buf = new ArrayBuffer(str.length)
	const view = new Uint8Array(buf)
	for (let i = 0; i < str.length; i++) {
		view[i] = str.charCodeAt(i)
	}
	return buf
}

export default {
	decryptData,
	encryptData
}
