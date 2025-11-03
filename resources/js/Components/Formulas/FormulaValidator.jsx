import React from 'react';
import { 
    IconCircleCheck, 
    IconCircleX, 
    IconAlertTriangle,
    IconInfoCircle
} from '@tabler/icons-react';

export default function FormulaValidator({ result }) {
    if (!result) return null;

    const { valid, errors = [], warnings = [], execution_result, execution_error } = result;

    return (
        <div className="mt-3">
            {/* Validation Status */}
            <div className="d-flex align-items-center mb-3">
                {valid ? (
                    <>
                        <IconCircleCheck size={20} className="text-success me-2" />
                        <span className="text-success fw-bold">✅ Formula is valid and ready to use!</span>
                    </>
                ) : (
                    <>
                        <IconCircleX size={20} className="text-danger me-2" />
                        <span className="text-danger fw-bold">❌ Formula has errors</span>
                    </>
                )}
            </div>

            {/* Errors */}
            {errors && errors.length > 0 && (
                <div className="alert alert-danger">
                    <div className="d-flex align-items-center mb-2">
                        <IconCircleX size={16} className="me-2" />
                        <strong>Validation Errors:</strong>
                    </div>
                    <ul className="mb-0">
                        {errors.map((error, index) => (
                            <li key={index}>{error}</li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Warnings */}
            {warnings && warnings.length > 0 && (
                <div className="alert alert-warning">
                    <div className="d-flex align-items-center mb-2">
                        <IconAlertTriangle size={16} className="me-2" />
                        <strong>Warnings:</strong>
                    </div>
                    <ul className="mb-0">
                        {warnings.map((warning, index) => (
                            <li key={index}>{warning}</li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Success with execution result */}
            {valid && (!errors || errors.length === 0) && (!warnings || warnings.length === 0) && (
                <div className="alert alert-success">
                    <div className="d-flex align-items-center mb-2">
                        <IconInfoCircle size={16} className="me-2" />
                        <strong>Formula validation successful!</strong>
                    </div>
                    <div>
                        <p className="mb-2">✅ Formula syntax is correct and ready to use.</p>
                        {execution_result !== null && execution_result !== undefined && (
                            <div className="mt-2">
                                <span className="fw-bold">Sample execution result: </span>
                                <span className="badge bg-secondary">{execution_result}</span>
                            </div>
                        )}
                        {execution_error && (
                            <div className="mt-2 text-danger">
                                <span className="fw-bold">Execution error: </span>
                                <span>{execution_error}</span>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}