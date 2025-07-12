# Attribution & Project Evolution

## Original Work

This project evolved from the excellent **Simple-MySQLi** library by WebsiteBeaver:
- **Original Repository**: https://github.com/WebsiteBeaver/Simple-MySQLi
- **Original Author**: WebsiteBeaver
- **Original License**: MIT
- **Original Version**: 1.5.5 (September 2018)

## Project Evolution

**SimpleMDB** began as a fork of Simple-MySQLi but has evolved into a completely different project:

### Original Simple-MySQLi (2017-2018)
- Single file: `simple-mysqli.php`
- Basic MySQLi wrapper
- Simple query building
- Fetch methods and basic operations
- ~600 lines of code

### SimpleMDB v3.0.0 (2025)
- **Enterprise Database Toolkit**
- 25+ data types with comprehensive validation
- 9 advanced column modifiers
- Intelligent migration system with context awareness
- Advanced schema builder
- Security-first design with SQL injection prevention
- Comprehensive documentation
- ~4,000+ lines of code across multiple classes

## Key Differences

| Feature | Simple-MySQLi | SimpleMDB |
|---------|---------------|-----------|
| **Purpose** | Basic MySQLi wrapper | Enterprise database toolkit |
| **Data Types** | Basic MySQL types | 25+ specialized types |
| **Schema Builder** | None | Full-featured with validation |
| **Migrations** | None | Intelligent system |
| **Security** | Basic | Enterprise-grade validation |
| **Documentation** | Basic | Comprehensive |
| **Architecture** | Single file | Multi-class PSR-4 |

## Acknowledgments

We are grateful to the original Simple-MySQLi project for providing the foundation that inspired this work. While SimpleMDB has evolved far beyond the original scope, we acknowledge the value of the original contribution to the PHP community.

## Version History & Compatibility

### SimpleMDB Version Timeline
- **v1.5.5 and earlier** - Original Simple-MySQLi by WebsiteBeaver
- **v2.0.0** - Initial fork with database toolkit features (transition period)
- **v2.1.0** - Enhanced migration system (transition period)
- **v3.0.0** - Complete transformation to SimpleMDB Enterprise Database Toolkit

### For Users of Earlier Versions

**If you're using v2.0.0 or v2.1.0:**
- These versions will continue to work but are considered transition releases
- Upgrade to v3.0.0 is highly recommended for production use
- All APIs remain backward compatible
- v3.0.0 adds 19 new data types and 9 new column modifiers
- Migration to v3.0.0 requires no code changes

**If you're using original Simple-MySQLi (v1.5.5 or earlier):**
- Consider this a completely different project now
- Migration guide available in documentation
- Significant API changes from original Simple-MySQLi

### Package Name Changes
- **Original**: Not available on Packagist
- **v2.0.0 - v2.1.0**: `simple-mysqli/simple-mysqli` (fork period)
- **v3.0.0+**: `simplemdb/simplemdb` (independent project)

## License Compatibility

Both projects use the MIT License, ensuring compatibility and allowing for this evolution while respecting the original work. 