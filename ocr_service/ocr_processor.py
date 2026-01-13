#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import json
import os
from PIL import Image, ImageEnhance, ImageFilter
import pytesseract
import re

def preprocess_image(image_path):
    """
    Preprocess image for better OCR accuracy using PIL only
    """
    try:
        # Open image
        img = Image.open(image_path)
        
        # Convert to RGB if needed
        if img.mode != 'RGB':
            img = img.convert('RGB')
        
        # Convert to grayscale
        img = img.convert('L')
        
        # Increase contrast
        enhancer = ImageEnhance.Contrast(img)
        img = enhancer.enhance(2.0)
        
        # Increase brightness slightly
        enhancer = ImageEnhance.Brightness(img)
        img = enhancer.enhance(1.2)
        
        # Sharpen
        img = img.filter(ImageFilter.SHARPEN)
        
        # Resize if too small (maintain aspect ratio)
        width, height = img.size
        if width < 1000 or height < 1000:
            scale = max(1000 / width, 1000 / height)
            new_size = (int(width * scale), int(height * scale))
            img = img.resize(new_size, Image.LANCZOS)
        
        return img
        
    except Exception as e:
        print(f"Error preprocessing image: {str(e)}", file=sys.stderr)
        return None

def extract_text(image_path):
    """
    Extract text from image using Tesseract OCR
    """
    try:
        # Preprocess image
        img = preprocess_image(image_path)
        if img is None:
            # Try original image if preprocessing fails
            img = Image.open(image_path)
        
        # Extract text with custom config for better results
        # --oem 3 = Use default OCR Engine mode
        # --psm 6 = Assume uniform block of text
        custom_config = r'--oem 3 --psm 6'
        text = pytesseract.image_to_string(img, lang='eng', config=custom_config)
        
        return text.strip()
        
    except Exception as e:
        print(f"Error extracting text: {str(e)}", file=sys.stderr)
        return None

def normalize_text(text):
    """
    Normalize text for comparison
    """
    if not text:
        return ""
    
    # Convert to lowercase
    text = text.lower()
    
    # Remove special characters but keep alphanumeric and spaces
    text = re.sub(r'[^a-z0-9\s]', '', text)
    
    # Remove extra whitespace
    text = re.sub(r'\s+', ' ', text)
    
    return text.strip()

def calculate_confidence(extracted_text, user_name, user_id_number):
    """
    Calculate confidence score based on matching
    Returns score from 0-100
    """
    if not extracted_text:
        return 0.0
    
    confidence = 0.0
    normalized_text = normalize_text(extracted_text)
    
    # 1. NAME MATCHING (50% weight)
    if user_name:
        normalized_name = normalize_text(user_name)
        name_parts = [part for part in normalized_name.split() if len(part) >= 2]
        
        if name_parts:
            found_parts = sum(1 for part in name_parts if part in normalized_text)
            name_confidence = (found_parts / len(name_parts)) * 50
            confidence += name_confidence
    
    # 2. ID NUMBER MATCHING (35% weight)
    if user_id_number:
        normalized_id = normalize_text(user_id_number)
        
        # Full match
        if normalized_id in normalized_text:
            confidence += 35
        else:
            # Partial match using segments
            id_length = len(normalized_id)
            if id_length >= 5:
                segment_size = id_length // 3
                segments = [
                    normalized_id[:segment_size],
                    normalized_id[segment_size:segment_size*2],
                    normalized_id[segment_size*2:]
                ]
                
                found_segments = sum(1 for seg in segments if seg and seg in normalized_text)
                confidence += (found_segments / 3) * 35
    
    # 3. ID KEYWORDS (15% weight)
    keywords = [
        'republic', 'philippines', 'driver', 'license', 
        'passport', 'national', 'identification', 'card',
        'birth', 'date', 'address', 'sex', 'issued', 'valid',
        'id', 'no', 'number'
    ]
    
    keyword_found = sum(1 for keyword in keywords if keyword in normalized_text)
    keyword_confidence = min((keyword_found / 4) * 15, 15)
    confidence += keyword_confidence
    
    return min(round(confidence, 2), 100.0)

def process_ocr(image_path, user_name, user_id_number):
    """
    Main OCR processing function
    """
    try:
        # Check if image exists
        if not os.path.exists(image_path):
            return {
                'success': False,
                'error': 'Image file not found: ' + image_path,
                'confidence': 0,
                'extracted_text': '',
                'normalized_text': ''
            }
        
        # Extract text
        extracted_text = extract_text(image_path)
        
        if extracted_text is None:
            return {
                'success': False,
                'error': 'Failed to extract text from image',
                'confidence': 0,
                'extracted_text': '',
                'normalized_text': ''
            }
        
        # Normalize text
        normalized_text = normalize_text(extracted_text)
        
        # Calculate confidence
        confidence = calculate_confidence(extracted_text, user_name, user_id_number)
        
        return {
            'success': True,
            'extracted_text': extracted_text,
            'normalized_text': normalized_text,
            'confidence': confidence,
            'error': None
        }
        
    except Exception as e:
        return {
            'success': False,
            'error': str(e),
            'confidence': 0,
            'extracted_text': '',
            'normalized_text': ''
        }

def main():
    """
    Command line interface
    """
    if len(sys.argv) < 4:
        result = {
            'success': False,
            'error': 'Usage: python ocr_processor.py <image_path> <user_name> <user_id_number>',
            'confidence': 0
        }
        print(json.dumps(result))
        sys.exit(1)
    
    image_path = sys.argv[1]
    user_name = sys.argv[2]
    user_id_number = sys.argv[3]
    
    # Process OCR
    result = process_ocr(image_path, user_name, user_id_number)
    
    # Output JSON result
    print(json.dumps(result, ensure_ascii=False))

if __name__ == "__main__":
    main()