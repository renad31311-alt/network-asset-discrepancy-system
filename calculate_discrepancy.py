"""
Calculates the discrepancy percentage and severity score between recorded
asset data and actual field survey data.
Called from PHP with 6 command-line arguments, returns the result as JSON.

Usage:
python calculate_discrepancy.py <recorded_length> <recorded_diameter> <recorded_material> <surveyed_length> <surveyed_diameter> <surveyed_material>
"""

import sys
import json

def calculate_discrepancy(recorded_length, recorded_diameter, recorded_material,
                            surveyed_length, surveyed_diameter, surveyed_material):

    # 1) Length difference percentage (the main numeric, continuous measure)
    if recorded_length == 0:
        length_diff_pct = 0
    else:
        length_diff_pct = round(abs(recorded_length - surveyed_length) / recorded_length * 100, 2)

    # 2) Is the diameter different? (boolean)
    diameter_mismatch = recorded_diameter != surveyed_diameter

    # 3) Is the material different? (boolean)
    material_mismatch = recorded_material.strip().lower() != surveyed_material.strip().lower()

    # 4) Overall severity score (simple weighted formula, 0 to 100)
    #    - length difference contributes proportionally
    #    - diameter mismatch adds 20 points (affects hydraulic capacity)
    #    - material mismatch adds 20 points (affects expected pipe lifespan)
    severity_score = length_diff_pct
    if diameter_mismatch:
        severity_score += 20
    if material_mismatch:
        severity_score += 20
    severity_score = round(min(severity_score, 100), 2)

    return {
        "length_diff_pct": length_diff_pct,
        "diameter_mismatch": diameter_mismatch,
        "material_mismatch": material_mismatch,
        "severity_score": severity_score
    }


if __name__ == "__main__":
    recorded_length = float(sys.argv[1])
    recorded_diameter = float(sys.argv[2])
    recorded_material = sys.argv[3]
    surveyed_length = float(sys.argv[4])
    surveyed_diameter = float(sys.argv[5])
    surveyed_material = sys.argv[6]

    result = calculate_discrepancy(
        recorded_length, recorded_diameter, recorded_material,
        surveyed_length, surveyed_diameter, surveyed_material
    )

    print(json.dumps(result))
