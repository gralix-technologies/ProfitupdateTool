import React, { useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { 
    Card, 
    CardHeader, 
    CardTitle, 
    CardContent 
} from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { 
    Search,
    Plus,
    Calculator,
    TrendingUp,
    BarChart3,
    Percent
} from 'lucide-react';

export default function FormulaTemplates({ onSelect }) {
    const [templates, setTemplates] = useState([]);
    const [filteredTemplates, setFilteredTemplates] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchTemplates();
    }, []);

    useEffect(() => {
        filterTemplates();
    }, [templates, searchTerm, selectedCategory]);

    const fetchTemplates = async () => {
        try {
            const response = await fetch('/formulas/templates');
            const data = await response.json();
            setTemplates(data.templates || []);
        } catch (error) {
            console.error('Failed to fetch templates:', error);
        } finally {
            setLoading(false);
        }
    };

    const filterTemplates = () => {
        let filtered = templates;

        if (searchTerm) {
            filtered = filtered.filter(template =>
                template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                template.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                template.expression.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }

        if (selectedCategory) {
            filtered = filtered.filter(template => template.category === selectedCategory);
        }

        setFilteredTemplates(filtered);
    };

    const categories = [...new Set(templates.map(t => t.category))];

    const getCategoryIcon = (category) => {
        const icons = {
            'Aggregation': BarChart3,
            'Mathematical': Calculator,
            'Financial': TrendingUp,
            'Statistical': BarChart3,
            'Conditional': Plus,
            'Percentage': Percent
        };
        return icons[category] || Calculator;
    };

    const getCategoryColor = (category) => {
        const colors = {
            'Aggregation': 'bg-blue-100 text-blue-800',
            'Mathematical': 'bg-green-100 text-green-800',
            'Financial': 'bg-purple-100 text-purple-800',
            'Statistical': 'bg-orange-100 text-orange-800',
            'Conditional': 'bg-yellow-100 text-yellow-800',
            'Percentage': 'bg-pink-100 text-pink-800'
        };
        return colors[category] || 'bg-gray-100 text-gray-800';
    };

    if (loading) {
        return (
            <Card>
                <CardContent className="pt-6">
                    <div className="flex items-center justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-lg">Formula Templates</CardTitle>
                <div className="space-y-4">
                    {/* Search */}
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                        <Input
                            placeholder="Search templates..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="pl-10"
                        />
                    </div>

                    {/* Category Filter */}
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant={selectedCategory === '' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setSelectedCategory('')}
                        >
                            All
                        </Button>
                        {categories.map((category) => (
                            <Button
                                key={category}
                                variant={selectedCategory === category ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setSelectedCategory(category)}
                            >
                                {category}
                            </Button>
                        ))}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-3 max-h-96 overflow-y-auto">
                    {filteredTemplates.map((template, index) => {
                        const IconComponent = getCategoryIcon(template.category);
                        return (
                            <div
                                key={index}
                                className="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                                onClick={() => onSelect(template)}
                            >
                                <div className="flex items-start justify-between mb-2">
                                    <div className="flex items-center gap-2">
                                        <IconComponent className="h-4 w-4 text-gray-600" />
                                        <h4 className="font-medium text-gray-900">{template.name}</h4>
                                    </div>
                                    <Badge className={getCategoryColor(template.category)}>
                                        {template.category}
                                    </Badge>
                                </div>
                                
                                <div className="mb-2">
                                    <code className="bg-gray-100 px-2 py-1 rounded text-sm font-mono">
                                        {template.expression}
                                    </code>
                                </div>
                                
                                <p className="text-sm text-gray-600">{template.description}</p>
                            </div>
                        );
                    })}

                    {filteredTemplates.length === 0 && (
                        <div className="text-center py-8 text-gray-500">
                            <Calculator className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                            <p>No templates found matching your criteria.</p>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}