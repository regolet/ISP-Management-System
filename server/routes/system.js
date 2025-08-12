const express = require('express');
const { exec } = require('child_process');
const fs = require('fs').promises;
const path = require('path');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Get current system version and status
router.get('/version', async (req, res) => {
  try {
    // Get current commit hash and message
    const getCurrentCommit = () => {
      return new Promise((resolve, reject) => {
        exec('git rev-parse HEAD', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout.trim());
        });
      });
    };

    const getCommitMessage = () => {
      return new Promise((resolve, reject) => {
        exec('git log -1 --pretty=%B', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout.trim());
        });
      });
    };

    const getCommitDate = () => {
      return new Promise((resolve, reject) => {
        exec('git log -1 --pretty=%ci', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout.trim());
        });
      });
    };

    const currentCommit = await getCurrentCommit();
    const commitMessage = await getCommitMessage();
    const commitDate = await getCommitDate();

    res.json({
      success: true,
      version: {
        commit: currentCommit.substring(0, 7),
        fullCommit: currentCommit,
        message: commitMessage,
        date: commitDate,
        branch: 'main'
      }
    });

  } catch (error) {
    console.error('Error getting system version:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to get system version',
      details: error.message 
    });
  }
});

// Check for available updates
router.get('/check-updates', async (req, res) => {
  try {
    // Fetch latest changes from remote
    const fetchUpdates = () => {
      return new Promise((resolve, reject) => {
        exec('git fetch origin main', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout);
        });
      });
    };

    // Get commits behind
    const getCommitsBehind = () => {
      return new Promise((resolve, reject) => {
        exec('git rev-list HEAD..origin/main --count', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(parseInt(stdout.trim()));
        });
      });
    };

    // Get latest remote commit info
    const getLatestRemoteCommit = () => {
      return new Promise((resolve, reject) => {
        exec('git log origin/main -1 --pretty=format:"%H|%s|%ci"', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          const [hash, message, date] = stdout.trim().replace(/"/g, '').split('|');
          resolve({ hash, message, date });
        });
      });
    };

    await fetchUpdates();
    const commitsBehind = await getCommitsBehind();
    
    let updateInfo = null;
    if (commitsBehind > 0) {
      updateInfo = await getLatestRemoteCommit();
    }

    res.json({
      success: true,
      updates: {
        available: commitsBehind > 0,
        commitsBehind: commitsBehind,
        latest: updateInfo ? {
          commit: updateInfo.hash.substring(0, 7),
          fullCommit: updateInfo.hash,
          message: updateInfo.message,
          date: updateInfo.date
        } : null
      }
    });

  } catch (error) {
    console.error('Error checking for updates:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to check for updates',
      details: error.message 
    });
  }
});

// Create backup before update
router.post('/backup', authenticateToken, async (req, res) => {
  try {
    const backupDir = path.join(process.cwd(), 'backups');
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const backupPath = path.join(backupDir, `backup-${timestamp}`);

    // Create backup directory if it doesn't exist
    try {
      await fs.access(backupDir);
    } catch {
      await fs.mkdir(backupDir, { recursive: true });
    }

    // Create backup using git archive
    const createBackup = () => {
      return new Promise((resolve, reject) => {
        exec(`git archive --format=tar --output="${backupPath}.tar" HEAD`, { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout);
        });
      });
    };

    await createBackup();

    res.json({
      success: true,
      backup: {
        path: `${backupPath}.tar`,
        timestamp: timestamp,
        message: 'Backup created successfully'
      }
    });

  } catch (error) {
    console.error('Error creating backup:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to create backup',
      details: error.message 
    });
  }
});

// Perform system update
router.post('/update', authenticateToken, async (req, res) => {
  try {
    const { createBackup = true } = req.body;

    let backupInfo = null;
    
    // Create backup if requested
    if (createBackup) {
      const backupDir = path.join(process.cwd(), 'backups');
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
      const backupPath = path.join(backupDir, `backup-${timestamp}`);

      try {
        await fs.access(backupDir);
      } catch {
        await fs.mkdir(backupDir, { recursive: true });
      }

      await new Promise((resolve, reject) => {
        exec(`git archive --format=tar --output="${backupPath}.tar" HEAD`, { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout);
        });
      });

      backupInfo = {
        path: `${backupPath}.tar`,
        timestamp: timestamp
      };
    }

    // Perform git pull
    const pullUpdates = () => {
      return new Promise((resolve, reject) => {
        exec('git pull origin main', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout);
        });
      });
    };

    // Install/update dependencies
    const updateDependencies = () => {
      return new Promise((resolve, reject) => {
        exec('npm install', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            // Don't fail the update if npm install fails, just log it
            console.warn('npm install warning:', error.message);
            resolve('npm install completed with warnings');
            return;
          }
          resolve(stdout);
        });
      });
    };

    const pullResult = await pullUpdates();
    const npmResult = await updateDependencies();

    // Get new version info
    const getCurrentCommit = () => {
      return new Promise((resolve, reject) => {
        exec('git rev-parse HEAD', { cwd: process.cwd() }, (error, stdout, stderr) => {
          if (error) {
            reject(error);
            return;
          }
          resolve(stdout.trim());
        });
      });
    };

    const newCommit = await getCurrentCommit();

    res.json({
      success: true,
      update: {
        completed: true,
        newVersion: newCommit.substring(0, 7),
        backup: backupInfo,
        pullOutput: pullResult,
        npmOutput: npmResult,
        message: 'System updated successfully. Server restart recommended.',
        restartRequired: true
      }
    });

  } catch (error) {
    console.error('Error performing update:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to perform update',
      details: error.message 
    });
  }
});

// Restart server (this will terminate the current process)
router.post('/restart', authenticateToken, async (req, res) => {
  try {
    res.json({
      success: true,
      message: 'Server restart initiated. Please wait a moment and refresh the page.'
    });

    // Give response time to send before restarting
    setTimeout(() => {
      console.log('Server restart requested via API');
      process.exit(0);
    }, 1000);

  } catch (error) {
    console.error('Error restarting server:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to restart server',
      details: error.message 
    });
  }
});

// Get backup list
router.get('/backups', authenticateToken, async (req, res) => {
  try {
    const backupDir = path.join(process.cwd(), 'backups');
    
    try {
      const files = await fs.readdir(backupDir);
      const backups = [];

      for (const file of files) {
        if (file.endsWith('.tar')) {
          const filePath = path.join(backupDir, file);
          const stats = await fs.stat(filePath);
          backups.push({
            name: file,
            path: filePath,
            size: stats.size,
            created: stats.birthtime,
            modified: stats.mtime
          });
        }
      }

      // Sort by creation date (newest first)
      backups.sort((a, b) => new Date(b.created) - new Date(a.created));

      res.json({
        success: true,
        backups: backups
      });

    } catch (error) {
      if (error.code === 'ENOENT') {
        res.json({
          success: true,
          backups: [],
          message: 'No backups directory found'
        });
      } else {
        throw error;
      }
    }

  } catch (error) {
    console.error('Error getting backup list:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to get backup list',
      details: error.message 
    });
  }
});

module.exports = router;