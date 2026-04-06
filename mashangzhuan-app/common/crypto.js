/**
 * 数据加密解密工具（纯 JS 实现，无第三方依赖）
 *
 * 算法与后端 DataEncryptService 完全一致：
 * 加密流程：JSON → XOR加密 → 字符偏移 → Base64
 * 解密流程：Base64 → 字符反偏移 → XOR解密 → JSON
 */

// ★ 密钥（必须与后端 DataEncryptService::SECRET_KEY 完全一致）
const SECRET_KEY = 'advnet2024@secure#aes256!key#data'

/**
 * 解密后端返回的加密数据
 *
 * @param {string} encryptedData Base64 编码的密文
 * @returns {Object|null} 解密后的数据对象，失败返回 null
 */
export function decryptData(encryptedData) {
	if (!encryptedData) return null

	// 如果不是字符串，说明未加密，直接返回
	if (typeof encryptedData !== 'string') return encryptedData

	// 如果看起来像 JSON 字符串（未加密降级），直接解析
	if (encryptedData.charAt(0) === '{' || encryptedData.charAt(0) === '[') {
		try {
			return JSON.parse(encryptedData)
		} catch (e) {
			return null
		}
	}

	try {
		// Base64 解码
		const decoded = base64Decode(encryptedData)
		if (!decoded) {
			console.warn('[Crypto] Base64解码失败')
			return null
		}

		// XOR 解密
		const json = xorDecrypt(decoded, SECRET_KEY)
		if (!json) {
			console.warn('[Crypto] 解密失败')
			return null
		}

		// JSON 解析
		const result = JSON.parse(json)
		console.log('[Crypto] 数据解密成功')
		return result
	} catch (e) {
		console.warn('[Crypto] 解密异常:', e.message)
		// 尝试直接作为 JSON 解析
		try {
			return JSON.parse(encryptedData)
		} catch (e2) {
			console.error('[Crypto] 数据解析完全失败')
			return null
		}
	}
}

/**
 * 加密数据（用于调试/测试）
 *
 * @param {Object} data 待加密的数据
 * @returns {string} Base64 编码的密文
 */
export function encryptData(data) {
	const json = JSON.stringify(data)
	const encrypted = xorEncrypt(json, SECRET_KEY)
	return base64Encode(encrypted)
}

// ==================== 核心算法 ====================

/**
 * XOR 流解密 + 位置反偏移（与后端 xorDecrypt 一致）
 *
 * @param {string} data Base64 解码后的密文
 * @param {string} key 密钥
 * @returns {string|null} 明文
 */
function xorDecrypt(data, key) {
	if (!data) return null

	const keyLen = key.length
	const dataLen = data.length
	const result = []

	for (let i = 0; i < dataLen; i++) {
		let charCode = data.charCodeAt(i)
		// 反位置偏移
		const offset = (i + key.charCodeAt((i * 3) % keyLen)) % 256
		charCode = ((charCode - offset) % 256 + 256) % 256
		// XOR with key
		charCode = charCode ^ key.charCodeAt(i % keyLen)
		result.push(String.fromCharCode(charCode))
	}

	return result.join('')
}

/**
 * XOR 流加密 + 位置偏移（与后端 xorEncrypt 一致）
 *
 * @param {string} data 明文
 * @param {string} key 密钥
 * @returns {string} 加密后的二进制字符串
 */
function xorEncrypt(data, key) {
	const keyLen = key.length
	const dataLen = data.length
	const result = []

	for (let i = 0; i < dataLen; i++) {
		// XOR with key
		let charCode = data.charCodeAt(i) ^ key.charCodeAt(i % keyLen)
		// 加位置偏移
		const offset = (i + key.charCodeAt((i * 3) % keyLen)) % 256
		charCode = (charCode + offset) % 256
		result.push(String.fromCharCode(charCode))
	}

	return result.join('')
}

// ==================== 编码工具 ====================

/**
 * Base64 解码（返回二进制字符串）
 */
function base64Decode(str) {
	try {
		const raw = atob(str)
		// atob 返回的是 "binary string"，每个字符是原始字节
		return raw
	} catch (e) {
		return null
	}
}

/**
 * Base64 编码
 */
function base64Encode(str) {
	try {
		return btoa(str)
	} catch (e) {
		return ''
	}
}

export default {
	decryptData,
	encryptData
}
