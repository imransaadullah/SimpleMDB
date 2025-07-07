<?php

namespace SimpleMDB\Seeding;

/**
 * Fake data generator for seeders
 */
class FakeDataGenerator
{
    private array $firstNames = [
        'John', 'Jane', 'Michael', 'Sarah', 'David', 'Lisa', 'Robert', 'Emily',
        'James', 'Jennifer', 'William', 'Amanda', 'Richard', 'Jessica', 'Joseph',
        'Ashley', 'Thomas', 'Brittany', 'Christopher', 'Stephanie', 'Daniel',
        'Nicole', 'Matthew', 'Elizabeth', 'Anthony', 'Helen', 'Mark', 'Deborah',
        'Donald', 'Rachel', 'Steven', 'Carolyn', 'Paul', 'Janet', 'Andrew',
        'Catherine', 'Kenneth', 'Maria', 'Brian', 'Ruth', 'George', 'Sharon',
        'Edward', 'Michelle', 'Ronald', 'Laura', 'Timothy', 'Sarah', 'Jason',
        'Kimberly'
    ];

    private array $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller',
        'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez',
        'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
        'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark',
        'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King',
        'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green',
        'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell',
        'Carter', 'Roberts'
    ];

    private array $companies = [
        'TechCorp', 'InnoSoft', 'DataFlow', 'CloudSync', 'WebSolutions',
        'DigitalVault', 'CyberNet', 'InfoSys', 'ByteForge', 'CodeCraft',
        'NetLogic', 'SystemDyne', 'TechFlow', 'DataBridge', 'CloudTech',
        'WebCore', 'DigitalFlow', 'CyberLogic', 'InfoFlow', 'ByteSync',
        'CodeFlow', 'NetBridge', 'SystemFlow', 'TechBridge', 'DataCore'
    ];

    private array $jobTitles = [
        'Software Engineer', 'Data Analyst', 'Product Manager', 'Marketing Manager',
        'Sales Representative', 'Customer Service Representative', 'Accountant',
        'Human Resources Manager', 'Operations Manager', 'Business Analyst',
        'Project Manager', 'Web Developer', 'Database Administrator',
        'System Administrator', 'Network Engineer', 'Security Analyst',
        'UX Designer', 'Quality Assurance Tester', 'DevOps Engineer',
        'Technical Writer', 'Sales Manager', 'Marketing Coordinator',
        'Financial Analyst', 'Administrative Assistant', 'Executive Assistant'
    ];

    private array $cities = [
        'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia',
        'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville',
        'San Francisco', 'Columbus', 'Charlotte', 'Fort Worth', 'Detroit',
        'El Paso', 'Memphis', 'Seattle', 'Denver', 'Washington', 'Boston',
        'Nashville', 'Baltimore', 'Oklahoma City', 'Portland', 'Las Vegas',
        'Louisville', 'Milwaukee', 'Albuquerque', 'Tucson', 'Fresno', 'Sacramento',
        'Kansas City', 'Long Beach', 'Mesa', 'Atlanta', 'Colorado Springs',
        'Virginia Beach', 'Raleigh', 'Omaha', 'Miami', 'Oakland', 'Minneapolis',
        'Tulsa', 'Wichita', 'New Orleans', 'Arlington'
    ];

    private array $states = [
        'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado',
        'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho',
        'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
        'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
        'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada',
        'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
        'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon',
        'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota',
        'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington',
        'West Virginia', 'Wisconsin', 'Wyoming'
    ];

    private array $domains = [
        'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com',
        'icloud.com', 'protonmail.com', 'example.com', 'test.org', 'demo.net'
    ];

    private array $streetTypes = [
        'Street', 'Avenue', 'Boulevard', 'Road', 'Lane', 'Drive', 'Way',
        'Circle', 'Court', 'Plaza', 'Square', 'Terrace', 'Place'
    ];

    private array $loremWords = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing',
        'elit', 'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore',
        'et', 'dolore', 'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam',
        'quis', 'nostrud', 'exercitation', 'ullamco', 'laboris', 'nisi',
        'aliquip', 'ex', 'ea', 'commodo', 'consequat', 'duis', 'aute', 'irure',
        'in', 'reprehenderit', 'voluptate', 'velit', 'esse', 'cillum', 'fugiat',
        'nulla', 'pariatur', 'excepteur', 'sint', 'occaecat', 'cupidatat',
        'non', 'proident', 'sunt', 'culpa', 'qui', 'officia', 'deserunt',
        'mollit', 'anim', 'id', 'est', 'laborum'
    ];

    /**
     * Generate random name
     */
    public function name(): string
    {
        return $this->randomChoice($this->firstNames) . ' ' . $this->randomChoice($this->lastNames);
    }

    /**
     * Generate random first name
     */
    public function firstName(): string
    {
        return $this->randomChoice($this->firstNames);
    }

    /**
     * Generate random last name
     */
    public function lastName(): string
    {
        return $this->randomChoice($this->lastNames);
    }

    /**
     * Generate random email
     */
    public function email(): string
    {
        $username = strtolower($this->firstName() . rand(1, 999));
        $domain = $this->randomChoice($this->domains);
        return $username . '@' . $domain;
    }

    /**
     * Generate random phone number
     */
    public function phone(): string
    {
        return sprintf(
            '(%03d) %03d-%04d',
            rand(100, 999),
            rand(100, 999),
            rand(1000, 9999)
        );
    }

    /**
     * Generate random company name
     */
    public function company(): string
    {
        return $this->randomChoice($this->companies);
    }

    /**
     * Generate random job title
     */
    public function jobTitle(): string
    {
        return $this->randomChoice($this->jobTitles);
    }

    /**
     * Generate random address
     */
    public function address(): string
    {
        $streetNumber = rand(1, 9999);
        $streetName = $this->randomChoice($this->firstNames) . ' ' . $this->randomChoice($this->streetTypes);
        return $streetNumber . ' ' . $streetName;
    }

    /**
     * Generate random city
     */
    public function city(): string
    {
        return $this->randomChoice($this->cities);
    }

    /**
     * Generate random state
     */
    public function state(): string
    {
        return $this->randomChoice($this->states);
    }

    /**
     * Generate random ZIP code
     */
    public function zipCode(): string
    {
        return sprintf('%05d', rand(10000, 99999));
    }

    /**
     * Generate random text
     */
    public function text(int $maxLength = 200): string
    {
        $words = [];
        $length = 0;
        
        while ($length < $maxLength) {
            $word = $this->randomChoice($this->loremWords);
            if ($length + strlen($word) + 1 <= $maxLength) {
                $words[] = $word;
                $length += strlen($word) + 1;
            } else {
                break;
            }
        }
        
        return implode(' ', $words);
    }

    /**
     * Generate random paragraph
     */
    public function paragraph(int $sentences = 5): string
    {
        $sentences_array = [];
        
        for ($i = 0; $i < $sentences; $i++) {
            $sentence = $this->sentence();
            $sentences_array[] = ucfirst($sentence) . '.';
        }
        
        return implode(' ', $sentences_array);
    }

    /**
     * Generate random sentence
     */
    public function sentence(int $words = 10): string
    {
        $sentence_words = [];
        
        for ($i = 0; $i < $words; $i++) {
            $sentence_words[] = $this->randomChoice($this->loremWords);
        }
        
        return implode(' ', $sentence_words);
    }

    /**
     * Generate random boolean
     */
    public function boolean(): bool
    {
        return rand(0, 1) === 1;
    }

    /**
     * Generate random number between min and max
     */
    public function numberBetween(int $min = 1, int $max = 100): int
    {
        return rand($min, $max);
    }

    /**
     * Generate random float between min and max
     */
    public function floatBetween(float $min = 0.0, float $max = 100.0): float
    {
        return $min + ($max - $min) * (rand() / getrandmax());
    }

    /**
     * Generate random date between two dates
     */
    public function dateBetween(string $startDate = '-1 year', string $endDate = 'now'): string
    {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        $randomTimestamp = rand($startTimestamp, $endTimestamp);
        
        return date('Y-m-d H:i:s', $randomTimestamp);
    }

    /**
     * Generate random UUID
     */
    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            rand(0, 0xffff), rand(0, 0xffff),
            rand(0, 0xffff),
            rand(0, 0x0fff) | 0x4000,
            rand(0, 0x3fff) | 0x8000,
            rand(0, 0xffff), rand(0, 0xffff), rand(0, 0xffff)
        );
    }

    /**
     * Generate random username
     */
    public function username(): string
    {
        return strtolower($this->firstName() . rand(1, 999));
    }

    /**
     * Generate random password
     */
    public function password(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Generate random price
     */
    public function price(float $min = 1.0, float $max = 1000.0): float
    {
        return round($this->floatBetween($min, $max), 2);
    }

    /**
     * Generate random URL
     */
    public function url(): string
    {
        $protocols = ['http', 'https'];
        $subdomains = ['www', 'app', 'api', 'blog', 'shop'];
        $domains = ['example.com', 'test.org', 'demo.net', 'sample.io'];
        
        $protocol = $this->randomChoice($protocols);
        $subdomain = $this->randomChoice($subdomains);
        $domain = $this->randomChoice($domains);
        
        return $protocol . '://' . $subdomain . '.' . $domain;
    }

    /**
     * Generate random IP address
     */
    public function ipAddress(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }

    /**
     * Generate random MAC address
     */
    public function macAddress(): string
    {
        return sprintf(
            '%02x:%02x:%02x:%02x:%02x:%02x',
            rand(0, 255), rand(0, 255), rand(0, 255),
            rand(0, 255), rand(0, 255), rand(0, 255)
        );
    }

    /**
     * Get random choice from array
     */
    private function randomChoice(array $choices)
    {
        return $choices[array_rand($choices)];
    }
} 